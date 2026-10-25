<?php

namespace Database\Factories;

use App\Models\DeliveryDispatch;
use App\Models\DeliveryProvider;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryDispatch>
 */
class DeliveryDispatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'delivery_provider_id' => DeliveryProvider::factory(),
            'requested_by' => User::factory(),
            'status' => DeliveryDispatch::STATUS_QUEUED,
            'customer_delivery_fee' => '2.00',
            'provider_fee' => '5.00',
            'currency_symbol' => 'ل.س',
            'queued_at' => now(),
        ];
    }
}
