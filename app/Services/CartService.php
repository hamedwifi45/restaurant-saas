<?php

namespace App\Services;

use App\Models\Restaurant;

class CartService
{
    /**
     * @return array<int, array{product_id: int, quantity: int, options: array}>
     */
    public function get(Restaurant $restaurant): array
    {
        $sessionKey = $this->sessionKey($restaurant);
        $storedCart = session($sessionKey, []);
        $cart = [];

        if (is_array($storedCart)) {
            foreach ($storedCart as $key => $details) {
                if (! is_array($details)) {
                    continue;
                }

                $productId = filter_var($details['product_id'] ?? $key, FILTER_VALIDATE_INT);
                $quantity = filter_var($details['quantity'] ?? $details['qty'] ?? null, FILTER_VALIDATE_INT);

                if (! $productId || $productId < 1 || ! $quantity || $quantity < 1) {
                    continue;
                }

                $cart[$productId] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'options' => $this->primitiveArray($details['options'] ?? []),
                ];
            }
        }

        if ($cart !== $storedCart) {
            session()->put($sessionKey, $cart);
        }

        return $cart;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    public function put(Restaurant $restaurant, array $cart): void
    {
        $normalizedCart = [];

        foreach ($cart as $key => $details) {
            if (! is_array($details)) {
                continue;
            }

            $productId = filter_var($details['product_id'] ?? $key, FILTER_VALIDATE_INT);
            $quantity = filter_var($details['quantity'] ?? null, FILTER_VALIDATE_INT);

            if (! $productId || $productId < 1 || ! $quantity || $quantity < 1) {
                continue;
            }

            $normalizedCart[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'options' => $this->primitiveArray($details['options'] ?? []),
            ];
        }

        session()->put($this->sessionKey($restaurant), $normalizedCart);
    }

    public function forget(Restaurant $restaurant): void
    {
        session()->forget($this->sessionKey($restaurant));
    }

    private function sessionKey(Restaurant $restaurant): string
    {
        return 'cart_'.$restaurant->id;
    }

    private function primitiveArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $primitiveArray = [];

        foreach ($value as $key => $item) {
            if (! is_int($key) && ! is_string($key)) {
                continue;
            }

            if (is_array($item)) {
                $primitiveArray[$key] = $this->primitiveArray($item);
            } elseif (is_null($item) || is_scalar($item)) {
                $primitiveArray[$key] = $item;
            }
        }

        return $primitiveArray;
    }
}
