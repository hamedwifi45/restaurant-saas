<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
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
        return 'DriverLocationUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'tracking_code' => $this->order->tracking_code,
            'driver_id' => $this->order->driver_id,
            'latitude' => $this->order->driver_latitude,
            'longitude' => $this->order->driver_longitude,
            'updated_at' => $this->order->driver_location_updated_at?->toIso8601String(),
        ];
    }
}
