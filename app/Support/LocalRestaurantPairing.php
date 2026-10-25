<?php

namespace App\Support;

use App\Models\Restaurant;

class LocalRestaurantPairing
{
    public function isEnabled(): bool
    {
        return (bool) config('local_operations.enabled');
    }

    public function assertMatches(Restaurant $restaurant): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        abort_unless(
            (int) config('local_operations.restaurant_id') === (int) $restaurant->id
                && hash_equals((string) config('local_operations.restaurant_slug'), (string) $restaurant->slug),
            404,
        );
    }

    public function restaurantForSlug(string $slug): Restaurant
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->assertMatches($restaurant);

        return $restaurant;
    }
}
