<?php

namespace App\Http\Controllers;

use App\Events\OrderReadyForDelivery;
use App\Helpers\ThemeHelper;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KdsController extends Controller
{
    /**
     * جلب كافة الطلبات النشطة لشاشة المطبخ (Pending, Preparing, Ready)
     */
    public function index($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        abort_unless((int) auth()->user()->restaurant_id === (int) $restaurant->id, 403);

        $themePath = ThemeHelper::getThemePath($restaurant);
        $orderCollection = Order::with(['items', 'restaurantTable', 'driver'])
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['pending', 'preparing'])
            ->get();

        $kdsOrders = $orderCollection->map(static fn (Order $order): array => [
            'id' => $order->tracking_code,
            'tracking_code' => $order->tracking_code,
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'customer_lat' => $order->customer_lat ?? $order->delivery_latitude,
            'customer_lng' => $order->customer_lng ?? $order->delivery_longitude,
            'distance_km' => $order->distance_km,
            'table' => $order->restaurantTable?->number,
            'items' => $order->items->map(static fn ($item): array => [
                'product_name' => $item->product_name,
                'name' => $item->name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->price,
                'total' => (float) $item->total,
            ])->values(),
        ])->values();

        return view("themes.{$themePath}.pages.kds-board", compact('restaurant', 'orderCollection', 'kdsOrders'));
    }

    /**
     * تحديث حالة الطلب عند تفاعل الطاهي مع الشاشة
     */
    public function updateStatus(Request $request, $orderId)
    {
        // 1. التحقق من صحة الحالة المجهزة
        $validated = $request->validate([
            'status' => 'required|in:new,preparing,ready,completed',
        ]);

        // 2. البحث عن الطلب وتحديث حالته
        $order = Order::where('tracking_code', $orderId)->firstOrFail();
        $restaurant = $order->restaurant;
        abort_unless((int) auth()->user()->restaurant_id === (int) $restaurant->id, 403);

        $order->status = match ($validated['status']) {
            'new' => 'pending',
            'completed' => 'delivered',
            default => $validated['status'],
        };
        $order->save();
        if ($order->status === 'ready') {
            broadcast(new OrderReadyForDelivery($order))->toOthers();
        }

        // 3. إرجاع استجابة JSON للـ JavaScript
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'status' => $validated['status'],
        ]);
    }

    public function rejectOrder(Request $request, string $orderId): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                Rule::in([
                    'خارج نطاق التوصيل',
                    'العنوان غير واضح',
                    'نفاد وجبات المطبخ',
                    'أخرى',
                ]),
            ],
        ]);

        $order = Order::where('tracking_code', $orderId)->firstOrFail();
        abort_unless((int) auth()->user()->restaurant_id === (int) $order->restaurant_id, 403);
        abort_if($order->status === 'cancelled', 409, 'تم رفض الطلب مسبقاً.');

        $order->update([
            'status' => 'cancelled',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'status' => $order->status,
            'rejection_reason' => $order->rejection_reason,
        ]);
    }
}
