<?php

namespace App\Http\Controllers;

use App\Events\OrderReadyForDelivery;
use App\Helpers\ThemeHelper;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;

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

        return view("themes.{$themePath}.pages.kds-board", compact('restaurant', 'orderCollection'));
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
}
