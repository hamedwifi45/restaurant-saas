<?php

if (! function_exists('theme_setting')) {
    function theme_setting(string $key, mixed $default = null): mixed
    {
        // جلب المطعم من الـ Route الحالي أو من المستخدم المسجل
        $restaurant = request()->get('current_restaurant') 
            ?? auth()->user()?->restaurant;

        if (! $restaurant) {
            return $default;
        }

        $savedSettings = $restaurant->themeSetting?->settings ?? [];

        // 1. إذا كانت القيمة محفوظة لدى المطعم، أرجعها
        if (array_key_exists($key, $savedSettings)) {
            return $savedSettings[$key];
        }

        // 2. وإلا، أرجع القيمة الافتراضية المحددة في allowed_variables للثيم
        $allowed = $restaurant->theme?->allowed_variables ?? [];

        return $allowed[$key]['default'] ?? $default;
    }
}