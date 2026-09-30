<?php

use Illuminate\Support\Str;

if (! function_exists('media_url')) {
    function media_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // إذا كان الرابط كاملاً بالفعل (روابط خارجية)
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // إزالة كلمة public/ أو الأسلاش الزائدة من بداية المسار إن وجدت
        $cleanPath = ltrim($path, '/');
        if (Str::startsWith($cleanPath, 'public/')) {
            $cleanPath = Str::after($cleanPath, 'public/');
        }

        return asset('storage/' . ltrim($cleanPath, '/'));
    }
}