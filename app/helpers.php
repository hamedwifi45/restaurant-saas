<?php

use Illuminate\Support\Facades\Cache;

if (! function_exists('theme_setting')) {
    function theme_setting(string $key, mixed $default = null): mixed
    {
        $restaurant = request()->get('current_restaurant') 
            ?? auth()->user()?->restaurant;

        if (! $restaurant) {
            return $default;
        }

        $cacheKey = "restaurant_theme_settings_{$restaurant->id}";

        $allSettings = Cache::remember($cacheKey, 86400, function () use ($restaurant) {
            $allowed = $restaurant->theme?->allowed_variables ?? [];
            $defaults = [];

            foreach ($allowed as $varKey => $varData) {
                $defaults[$varKey] = $varData['default'] ?? null;
            }

            $saved = $restaurant->themeSettings?->settings 
                ?? $restaurant->theme_settings 
                ?? [];

            return array_merge($defaults, $saved);
        });

        $value = $allSettings[$key] ?? $default;

        if ($key === 'border_radius' && is_numeric($value)) {
            return $value . 'px';
        }

        return $value;
    }
}