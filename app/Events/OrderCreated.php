<?php

namespace App\Events;

use App\Http\Resources\KdsOrderResource;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order->load(['items', 'restaurantTable', 'driver']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        Log::info(
            "📣 [Server Broadcast] Dispatching OrderCreated event for Order ID: {$this->order->id} on channel: restaurant.{$this->order->restaurant_id}.kds"
        );

        return [
            new PrivateChannel('restaurant.'.$this->order->restaurant_id.'.kds'),
            new PrivateChannel('restaurant.'.$this->order->restaurant_id.'.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'order' => (new KdsOrderResource($this->order))->resolve(),
        ];
    }
}
