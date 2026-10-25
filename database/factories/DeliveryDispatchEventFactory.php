<?php

namespace Database\Factories;

use App\Models\DeliveryDispatch;
use App\Models\DeliveryDispatchEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryDispatchEvent>
 */
class DeliveryDispatchEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_dispatch_id' => DeliveryDispatch::factory(),
            'actor_id' => User::factory(),
            'from_status' => null,
            'to_status' => DeliveryDispatch::STATUS_QUEUED,
            'note' => 'تم تسجيل الإحالة يدوياً.',
        ];
    }
}
