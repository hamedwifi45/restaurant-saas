<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\RestaurantTable;

class RestaurantTableSession
{
    public function resolve(Restaurant $restaurant): ?RestaurantTable
    {
        $legacyTableId = session('table_id');
        $tableId = session('restaurant_table_id');
        $sessionRestaurantId = session('restaurant_table_restaurant_id');

        if (
            session()->has('table')
            || ($legacyTableId !== null && $this->identifier($legacyTableId) === null)
            || ($tableId !== null && $this->identifier($tableId) === null)
            || ($sessionRestaurantId !== null && $this->identifier($sessionRestaurantId) === null)
        ) {
            $this->forget();

            return null;
        }

        $legacyTableId = $this->identifier($legacyTableId);
        $tableId = $this->identifier($tableId);
        $sessionRestaurantId = $this->identifier($sessionRestaurantId);

        if ($legacyTableId !== null && $tableId !== null && $legacyTableId !== $tableId) {
            $this->forget();

            return null;
        }

        if ($sessionRestaurantId !== null && $sessionRestaurantId !== $restaurant->id) {
            $this->forget();

            return null;
        }

        $tableId = $legacyTableId ?? $tableId;

        if ($tableId === null) {
            if ($sessionRestaurantId !== null) {
                $this->forget();
            }

            return null;
        }

        $table = $restaurant->tables()
            ->whereKey($tableId)
            ->where('is_active', true)
            ->first();

        if (! $table) {
            $this->forget();

            return null;
        }

        $this->remember($restaurant, $table);

        return $table;
    }

    public function findByIdentifier(Restaurant $restaurant, mixed $identifier): ?RestaurantTable
    {
        if (! is_string($identifier) && ! is_int($identifier)) {
            return null;
        }

        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $tables = $restaurant->tables()->where('is_active', true);
        $table = (clone $tables)->where('number', $identifier)->first();

        if (! $table && ctype_digit($identifier)) {
            $table = $tables->whereKey((int) $identifier)->first();
        }

        return $table;
    }

    public function remember(Restaurant $restaurant, RestaurantTable $table): void
    {
        session()->forget(['table', 'table_id']);
        session()->put([
            'restaurant_table_id' => $table->id,
            'restaurant_table_restaurant_id' => $restaurant->id,
        ]);
    }

    public function forget(): void
    {
        session()->forget([
            'table',
            'table_id',
            'restaurant_table_id',
            'restaurant_table_restaurant_id',
        ]);
    }

    private function identifier(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value) || ! ctype_digit($value)) {
            return null;
        }

        $identifier = (int) $value;

        return $identifier > 0 ? $identifier : null;
    }
}
