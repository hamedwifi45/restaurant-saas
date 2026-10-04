<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant.'.$this->order->restaurant_id.'.admin')];
    }

    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'tracking_code' => $this->order->tracking_code,
                'status' => $this->order->status,
                'driver_id' => $this->order->driver_id,
                'driver_name' => $this->order->driverUser?->name,
                'driver_latitude' => $this->order->driver_latitude,
                'driver_longitude' => $this->order->driver_longitude,
                'customer_name' => $this->order->customer_name,
                'delivery_address' => $this->order->delivery_address,
                'customer_latitude' => $this->order->delivery_latitude ?? $this->order->customer_lat,
                'customer_longitude' => $this->order->delivery_longitude ?? $this->order->customer_lng,
            ],
        ];
    }
}
