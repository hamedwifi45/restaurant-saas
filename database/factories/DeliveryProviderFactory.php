<?php

namespace Database\Factories;

use App\Models\DeliveryProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryProvider>
 */
class DeliveryProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'pricing_notes' => fake()->sentence(),
            'assignment_method' => 'manual',
            'is_active' => true,
        ];
    }
}
