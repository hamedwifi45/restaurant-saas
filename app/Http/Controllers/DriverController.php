<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function dashboard(Request $request, string $slug): View
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        abort_unless(
            $user?->canManageDriverDashboardFor($restaurant),
            403,
            'غير مصرح لك بإدارة لوحة التوصيل لهذا المطعم.'
        );

        $deliveryOrders = $restaurant->orders()->where('delivery_type', 'delivery');
        $dispatchedOrders = (clone $deliveryOrders)
            ->whereNotNull('driver_id')
            ->whereIn('status', ['on_way', 'out_for_delivery', 'in_transit'])
            ->with('driverUser')
            ->latest('updated_at')
            ->get();
        $activeOrderStatuses = ['delivered', 'cancelled', 'rejected'];
        $drivers = User::query()
            ->where('restaurant_id', $restaurant->id)
            ->where(fn ($query) => $query
                ->where('role', 'driver')
                ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'driver')))
            ->with([
                'driverProfile',
                'assignedOrders' => fn ($orders) => $orders
                    ->where('restaurant_id', $restaurant->id)
                    ->where('delivery_type', 'delivery')
                    ->whereIn('status', ['on_way', 'out_for_delivery', 'in_transit'])
                    ->latest('updated_at'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $driver): array {
                $currentOrder = $driver->assignedOrders->first();
                $isAvailable = (bool) $driver->is_active && (bool) $driver->driverProfile?->is_available;
                $status = $currentOrder ? 'on_delivery' : ($isAvailable ? 'available' : 'offline');

                return [
                    'name' => $driver->name,
                    'phone' => $driver->driverProfile?->phone ?? $driver->phone,
                    'status' => $status,
                    'current_order' => $currentOrder,
                ];
            });
        $metrics = [
            'active' => (clone $deliveryOrders)->whereNotIn('status', $activeOrderStatuses)->count(),
            'completed' => (clone $deliveryOrders)->where('status', 'delivered')->count(),
            'dispatched' => $dispatchedOrders->count(),
            'drivers' => $drivers->count(),
        ];
        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.driver.dashboard", compact(
            'restaurant',
            'metrics',
            'drivers',
            'dispatchedOrders'
        ));
    }

    public function index(Request $request, string $slug): View
    {
        $driver = $request->user();
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        abort_unless(
            $driver?->canViewDriverOrdersFor($restaurant),
            403,
            'غير مصرح لك بالوصول لطلبات هذا المطعم.'
        );

        $myActiveOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('delivery_type', 'delivery')
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ready', 'on_way', 'accepted'])
            ->with(['restaurant', 'items.product', 'driver'])
            ->latest()
            ->get();

        $availableOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('delivery_type', 'delivery')
            ->whereNull('driver_id')
            ->where('status', 'ready')
            ->with(['restaurant', 'items.product'])
            ->latest()
            ->get();

        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.driver.orders", compact('restaurant', 'myActiveOrders', 'availableOrders'));
    }

    public function acceptOrder(Request $request, string $slug, Order $order)
    {
        $driver = $request->user();
        $this->authorizeDriverTenantOrder($request, $slug, $order);

        abort_unless(
            $order->delivery_type === 'delivery'
                && $order->driver_id === null
                && $order->status === 'ready',
            403
        );

        // التأكد من أن المندوب لا يملك طلباً جارٍ توصيله حالياً
        $hasActiveOrder = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['ready', 'on_way'])
            ->exists();

        if ($hasActiveOrder) {
            // إضافة رمز 422 ليتعرف JavaScript على وجود خطأ
            return response()->json([
                'message' => 'لديك طلب نشط بالفعل، يجب إكماله وتسليمه أولاً.',
            ], 422);
        }

        // ربط الطلب بالمندوب وتحويل حالته إلى on_way
        $order->update([
            'driver_id' => $driver->id,
            'status' => 'on_way',
        ]);

        return response()->json([
            'message' => 'تم استلام الطلب بنجاح.',
        ]);
    }

    public function show(Request $request, string $slug, Order $order): JsonResponse
    {
        $this->authorizeDriverOrderView($request, $slug, $order);

        return response()->json($this->orderPayload($order->loadMissing(['restaurant', 'items.product'])));
    }

    public function tracking(Request $request, string $slug, Order $order): View|RedirectResponse
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        abort_unless($order->restaurant_id === $restaurant->id, 404);

        $isDriver = $user?->canAccessDriverPanelFor($restaurant)
            && $order->driver_id === $user->id;
        $requestToken = $request->query('token');
        $hasTrackingToken = filled($order->device_token)
            && is_string($requestToken)
            && hash_equals($order->device_token, $requestToken);
        $hasDeviceCookie = filled($order->device_token)
            && is_string($request->cookie('order_device_token_'.$order->id))
            && hash_equals($order->device_token, $request->cookie('order_device_token_'.$order->id));
        $isAdmin = $user?->role === 'super_admin'
            || ($user?->isRestaurantAdmin() && (int) $user->restaurant_id === (int) $restaurant->id);

        abort_unless(
            $isDriver || $hasTrackingToken || $hasDeviceCookie || $isAdmin,
            403,
            'غير مصرح لك بالوصول لصفحة التتبع إلا من الجهاز الذي تم إنشاء الطلب منه.'
        );

        if ($order->status === 'delivered' && ! $user) {
            return redirect()->route('order.invoice', ['slug' => $slug, 'trackingCode' => $order->tracking_code]);
        }
        if ($order->status === 'delivered') {
            return redirect()->route('driver.orders', ['slug' => $slug]);
        }

        $themePath = ThemeHelper::getThemePath($restaurant);
        $order->load(['restaurant', 'driver', 'items.product']);

        // إرسال المتغير isDriver لملف الـ Blade لتحديد هل تظهر لوحة التحكم أم العرض فقط
        return view("themes.{$themePath}.driver.tracking", compact('order', 'restaurant', 'isDriver', 'slug'));
    }

    public function updateLocation(Request $request, string $slug, Order $order): JsonResponse
    {
        $this->authorizeDriverOrder($request, $slug, $order);

        abort_unless(in_array($order->status, ['ready', 'on_way'], true), 422, 'تم إغلاق تتبع هذا الطلب.');

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $order->forceFill([
            'driver_latitude' => $validated['latitude'],
            'driver_longitude' => $validated['longitude'],
            'driver_location_updated_at' => now(),
            'status' => $order->status === 'ready' ? 'on_way' : $order->status,
        ])->save();

        return response()->json([
            'success' => true,
            'updated_at' => $order->driver_location_updated_at?->toIso8601String(),
            'status' => $order->status,
        ]);
    }

    public function updateStatus(Request $request, string $slug, Order $order): JsonResponse
    {
        $this->authorizeDriverOrder($request, $slug, $order);

        $validated = $request->validate([
            'status' => ['required', 'in:on_way,delivered'],
        ]);

        abort_unless(
            ($validated['status'] === 'on_way' && $order->status === 'ready')
                || ($validated['status'] === 'delivered' && $order->status === 'on_way'),
            422,
            'لا يمكن تغيير حالة الطلب من الحالة الحالية.'
        );

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'status' => $order->status,
        ]);
    }

    public function markArrived(Request $request, string $slug, Order $order): JsonResponse
    {
        $this->authorizeDriverOrder($request, $slug, $order);

        abort_unless($order->status === 'on_way', 422, 'يمكن تسجيل الوصول بعد بدء التوصيل فقط.');

        $arrivedAt = now();
        $updated = Order::query()
            ->whereKey($order->getKey())
            ->whereNull('driver_arrived_at')
            ->update(['driver_arrived_at' => $arrivedAt]);

        if ($updated === 1) {
            $order->forceFill(['driver_arrived_at' => $arrivedAt]);
            $order->notify((new OrderStatusUpdatedNotification($order, 'driver_arrived'))->afterCommit());
        }

        return response()->json([
            'success' => true,
            'already_notified' => $updated === 0,
        ]);
    }

    private function authorizeDriverOrder(Request $request, string $slug, Order $order): void
    {
        $driver = $request->user();
        $restaurant = Restaurant::query()->where('slug', $slug)->firstOrFail();

        abort_unless(
            $driver?->canAccessDriverPanelFor($restaurant)
                && $order->restaurant_id === $restaurant->id
                && $order->driver_id === $driver->id,
            403,
            'غير مصرح لك بإجراء تغييرات على هذا الطلب.'
        );
    }

    private function authorizeDriverOrderView(Request $request, string $slug, Order $order): void
    {
        $user = $request->user();
        $restaurant = Restaurant::query()->where('slug', $slug)->firstOrFail();

        abort_unless(
            $user?->canViewDriverOrdersFor($restaurant)
                && $order->restaurant_id === $restaurant->id,
            403,
            'غير مصرح لك بالوصول إلى طلبات هذا المطعم.'
        );

        abort_unless(
            $user->isRestaurantAdmin() || $order->driver_id === $user->id,
            403,
            'لا تملك صلاحية الوصول لهذا الطلب.'
        );
    }

    private function authorizeDriverTenantOrder(Request $request, string $slug, Order $order): void
    {
        $restaurant = Restaurant::query()->where('slug', $slug)->firstOrFail();
        $driver = $request->user();

        abort_unless(
            $driver?->canAccessDriverPanelFor($restaurant)
                && $order->restaurant_id === $restaurant->id,
            403,
            'غير مصرح لك بالوصول إلى طلبات هذا المطعم.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'tracking_code' => $order->tracking_code,
            'status' => $order->status,
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'address' => $order->delivery_address,
                'latitude' => $order->delivery_latitude,
                'longitude' => $order->delivery_longitude,
            ],
            'driver' => [
                'latitude' => $order->driver_latitude,
                'longitude' => $order->driver_longitude,
                'updated_at' => $order->driver_location_updated_at?->toIso8601String(),
            ],
            'restaurant' => [
                'name' => $order->restaurant?->name,
            ],
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->product?->name ?? 'منتج محذوف',
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }
}
