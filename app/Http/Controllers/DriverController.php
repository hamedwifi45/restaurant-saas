<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Order;
use App\Models\Restaurant;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function dashboard(Request $request, string $slug): View
    {
        $driver = $request->user();

        // 1. جلب المطعم المطلوب
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        // 2. التحقق من دور السائق وربطه بنفس المطعم
        abort_unless(
            $driver?->role === 'driver' && $driver->restaurant_id === $restaurant->id,
            403,
            'غير مصرح لك بالوصول للوحة تحكم هذا المطعم.'
        );

        // 3. جلب الطلبات النشطة للسائق داخل هذا المطعم فقط
        $activeOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ready', 'on_way'])
            ->with('restaurant')
            ->latest()
            ->get();

        // 4. تحديد الطلب المميز للخريطة مباشرة في الكنترولر
        $featuredOrder = $activeOrders->first();

        // 5. إحصائيات اليوم الخاصة بهذا المطعم والسائق
        $todayOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('driver_id', $driver->id)
            ->whereDate('created_at', today());

        $stats = [
            'active' => $activeOrders->count(),
            'deliveredToday' => (clone $todayOrders)->where('status', 'delivered')->count(),
            'totalToday' => $todayOrders->count(),
        ];

        // 6. الطلبات المكتملة حديثاً لنفس المطعم
        $recentOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->with('restaurant')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // 7. تحديد مسار الثيم واستدعاء العرض
        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.driver.dashboard", compact(
            'driver',
            'restaurant',
            'activeOrders',
            'recentOrders',
            'stats',
            'slug',
            'featuredOrder' // تم إضافة القيمة هنا بدلاً من حسابها في Blade
        ));
    }

    public function index(Request $request, string $slug)
    {
        $driver = $request->user();
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        abort_unless(
            $driver?->role === 'driver' && $driver->restaurant_id === $restaurant->id,
            403,
            'غير مصرح لك بالوصول لطلبات هذا المطعم.'
        );

        $myActiveOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ready', 'on_way', 'accepted'])
            ->with(['restaurant', 'items.product'])
            ->latest()
            ->get();

        $availableOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
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

        abort_unless($order->driver_id === null && $order->status === 'ready', 403);

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
        $this->authorizeDriverTenantOrder($request, $slug, $order);

        if ($order->driver_id !== $request->user()->id) {
            abort(403, 'لا تملك صلاحية الوصول لهذا الطلب.');
        }

        return response()->json($this->orderPayload($order->loadMissing(['restaurant', 'items.product'])));
    }

    public function tracking(Request $request, string $slug, Order $order): View|RedirectResponse
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        abort_unless($order->restaurant_id === $restaurant->id, 404);

        if ($order->status === 'delivered' && ! $user) {
            return redirect()->route('order.invoice', ['slug' => $slug, 'trackingCode' => $order->tracking_code]);
        }
        if ($order->status === 'delivered') {
            return redirect()->route('driver.orders', ['slug' => $slug]);
        }

        $isDriver = false;

        // 2. فحص ما إذا كان الزائر هو السائق المسؤول عن الطلب
        if ($user && $user->role === 'driver' && $user->restaurant_id === $restaurant->id && $order->driver_id === $user->id) {
            $isDriver = true;
        } else {
            $hasDeviceCookie = filled($order->device_token)
                && request()->cookie('order_device_token_'.$order->id) === $order->device_token;
            $isAdmin = $user?->role === 'super_admin'
                || ($user?->role === 'restaurant_admin' && $user->restaurant_id === $restaurant->id);
            // $savedCookie = $request->cookie('order_device_token_' . $order->id);

            if (! $hasDeviceCookie && ! $isAdmin) {
                abort(403, 'غير مصرح لك بالوصول لصفحة التتبع إلا من الجهاز الذي تم إنشاء الطلب منه.');
            }
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
            $driver?->role === 'driver'
                && $driver->restaurant_id === $restaurant->id
                && $order->restaurant_id === $restaurant->id
                && $order->driver_id === $driver->id,
            403,
            'غير مصرح لك بإجراء تغييرات على هذا الطلب.'
        );
    }

    private function authorizeDriverTenantOrder(Request $request, string $slug, Order $order): void
    {
        $restaurant = Restaurant::query()->where('slug', $slug)->firstOrFail();
        $driver = $request->user();

        abort_unless(
            $driver?->role === 'driver'
                && $driver->restaurant_id === $restaurant->id
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
