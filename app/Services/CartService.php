<?php

namespace App\Services;

use App\Models\Restaurant;

class CartService
{
    /**
     * @return array<int|string, array{product_id: int, quantity: int, unit_price?: float, variant_name?: string|null, price_type?: string, options: array}>
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

                $cartKey = is_string($key) && ! ctype_digit($key) ? $key : $productId;
                $cart[$cartKey] = $this->normalizeDetails($details, $productId, $quantity);
            }
        }

        if ($cart !== $storedCart) {
            session()->put($sessionKey, $cart);
        }

        return $cart;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $cart
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

            $cartKey = is_string($key) && ! ctype_digit($key) ? $key : $productId;
            $normalizedCart[$cartKey] = $this->normalizeDetails($details, $productId, $quantity);
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

    /**
     * @param  array<string, mixed>  $details
     * @return array{product_id: int, quantity: int, unit_price?: float, variant_name?: string|null, price_type?: string, options: array}
     */
    private function normalizeDetails(array $details, int $productId, int $quantity): array
    {
        $normalized = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'options' => $this->primitiveArray($details['options'] ?? []),
        ];

        if (isset($details['unit_price']) && is_numeric($details['unit_price']) && (float) $details['unit_price'] >= 0) {
            $normalized['unit_price'] = (float) $details['unit_price'];
        }

        if (isset($details['variant_name']) && is_scalar($details['variant_name'])) {
            $normalized['variant_name'] = (string) $details['variant_name'];
        } elseif (array_key_exists('variant_name', $details)) {
            $normalized['variant_name'] = null;
        }

        if (isset($details['price_type']) && is_string($details['price_type'])) {
            $normalized['price_type'] = $details['price_type'];
        }

        return $normalized;
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
