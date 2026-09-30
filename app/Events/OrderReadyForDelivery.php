<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReadyForDelivery implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $orderData;
    public int $restaurantId;

    public function __construct(Order $order)
    {
        $this->restaurantId = $order->restaurant_id;

        // تجهيز بيانات الطلب لإرسالها للوحة المندوب مباشرة
        $this->orderData = [
            'id' => $order->id,
            'tracking_code' => $order->tracking_code,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'latitude' => $order->latitude ?? $order->delivery_latitude,
            'longitude' => $order->longitude ?? $order->delivery_longitude,
            'items_count' => $order->items->count(),
            'final_amount' => $order->final_amount,
        ];
    }

    public function broadcastOn(): array
    {
        // قناة خاصة بمندوبي هذا المطعم حصراً
        return [
            new PrivateChannel('restaurant.' . $this->restaurantId . '.drivers'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.ready';
    }
}