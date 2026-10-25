<?php

namespace App\Services;

use App\Models\LocalOrderSyncOutbox;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LocalOrderIntakeService
{
    public function __construct(private readonly PosOrderService $orders) {}

    /**
     * @param  array<string, mixed>  $cart
     * @param  array<string, mixed>  $checkout
     * @return array{order: Order, created: bool}
     */
    public function accept(
        Restaurant $restaurant,
        User $cashier,
        array $cart,
        array $checkout,
        string $clientOrderUuid,
    ): array {
        return DB::transaction(function () use ($restaurant, $cashier, $cart, $checkout, $clientOrderUuid): array {
            $order = $this->orders->createOrder(
                $restaurant,
                $cashier,
                $cart,
                $checkout,
                $clientOrderUuid,
                createInvoice: false,
            );

            $created = $order->wasRecentlyCreated;
            $order->loadMissing('items');

            LocalOrderSyncOutbox::query()->firstOrCreate(
                ['order_id' => $order->id],
                [
                    'restaurant_id' => $restaurant->id,
                    'event_uuid' => $clientOrderUuid,
                    'payload' => [
                        'event_uuid' => $clientOrderUuid,
                        'client_order_uuid' => $clientOrderUuid,
                        'restaurant_slug' => $restaurant->slug,
                        'tracking_code' => $order->tracking_code,
                        'created_at' => $order->created_at?->toIso8601String(),
                        'customer_name' => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'delivery_type' => $order->delivery_type,
                        'delivery_address' => $order->delivery_address,
                        'subtotal' => (string) $order->subtotal,
                        'tax_amount' => (string) $order->tax_amount,
                        'delivery_fee' => (string) $order->delivery_fee,
                        'discount' => (string) $order->discount,
                        'total_amount' => (string) $order->final_amount,
                        'payment_status' => $order->payment_status,
                        'status' => $order->status,
                        'items' => $order->items->map(static fn ($item): array => [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'variant_name' => $item->variant_name,
                            'quantity' => $item->quantity,
                            'unit_price' => (string) $item->price,
                            'total' => (string) $item->total,
                            'notes' => $item->notes,
                        ])->all(),
                    ],
                    'status' => 'pending',
                    'next_attempt_at' => now(),
                ],
            );

            return ['order' => $order, 'created' => $created];
        }, 3);
    }
}
