<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function dashboard(string $slug): View
    {
        $driver = auth()->user();

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

        // 1. جلب المطعم
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        // 2. التحقق من صلاحيات المندوب
        abort_unless(
            $driver?->role === 'driver' && $driver->restaurant_id === $restaurant->id,
            403,
            'غير مصرح لك بالوصول لطلبات هذا المطعم.'
        );

        // 3. البحث عن الطلب الخاص بالمندوب إذا كان لديه طلب قيد التوصيل حالياً
        $activeOrder = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['ready', 'on_way'])
            ->with(['restaurant', 'items.product'])
            ->latest()
            ->get();

        // 4. الشرط: إذا كان يملك طلباً نشطاً يتم إرجاعه فقط، وإلا يتم جلب الطلبات الشاغرة
        if ($activeOrder->isNotEmpty()) {
            $order = $activeOrder->first();
            return redirect()->route('driver.order.tracking', [$slug, $order->tracking_code]);
        } else {
            $orders = Order::query()
                ->where('restaurant_id', $restaurant->id)
                ->whereNull('driver_id')
                ->where('status', 'ready')
                ->with(['restaurant', 'items.product'])
                ->latest()
                ->get();
        }

        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.driver.orders", compact('orders', 'restaurant'));
    }

    public function acceptOrder(Request $request, string $slug, Order $order)
    {
        $driver = $request->user();

        // التأكد من أن المندوب لا يملك طلباً جارٍ توصيله حالياً
        $hasActiveOrder = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['ready', 'on_way'])
            ->exists();

        if ($hasActiveOrder) {
            // إضافة رمز 422 ليتعرف JavaScript على وجود خطأ
            return response()->json([
                'message' => 'لديك طلب نشط بالفعل، يجب إكماله وتسليمه أولاً.'
            ], 422);
        }

        // ربط الطلب بالمندوب وتحويل حالته إلى on_way
        $order->update([
            'driver_id' => $driver->id,
            'status'    => 'on_way',
        ]);

        return response()->json([
            'message' => 'تم استلام الطلب بنجاح.'
        ]);
    }
    public function show(Request $request, string $slug, Order $order): JsonResponse
    {
        if (!is_null($order->driver_id) && $order->driver_id !== auth()->id()) {
            abort(403, 'لا تملك صلاحية الوصول لهذا الطلب.');
        }

        return response()->json($this->orderPayload($order->loadMissing(['restaurant', 'items.product'])));
    }

    public function tracking(Request $request, string $slug, Order $order): View|RedirectResponse
    {
        // 1. التوجيه لصفحة الفاتورة فور اكتمال الطلب وتسليمه
        if ($order->status === 'delivered') {
            // يمكنك تغيير اسم الروت 'invoice.show' للروت الخاض بالفاتورة لديك لاحقاً
            return redirect()->route('invoice.show', ['slug' => $slug, 'order' => $order->tracking_code]);
        }

        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $user = $request->user();
        $isDriver = false;

        // 2. فحص ما إذا كان الزائر هو السائق المسؤول عن الطلب
        if ($user && $user->role === 'driver' && $user->restaurant_id === $restaurant->id && $order->driver_id === $user->id) {
            $isDriver = true;
        } else {
            // 3. إذا لم يكن السائق، يتم التحقق من الكوكي للتأكد أنه المشتري ومن نفس الجهاز
            $savedCookie = $request->cookie('order_device_token_' . $order->id);

            if (!$savedCookie || $savedCookie !== $order->device_token) {
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
        $this->authorizeDriverOrder($request, $order);

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
        $this->authorizeDriverOrder($request, $order);

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

    private function authorizeDriverOrder(Request $request, Order $order): void
    {
        $driver = $request->user();

        abort_unless(
            $driver?->role === 'driver'
                && $driver->restaurant_id === $order->restaurant_id
                && $order->driver_id === $driver->id,
            403,
            'غير مصرح لك بإجراء تغييرات على هذا الطلب.'
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
            'items' => $order->items->map(fn($item): array => [
                'name' => $item->product?->name ?? 'منتج محذوف',
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }
}
