<?php

namespace App\Helpers;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;

class ThemeHelper
{
    /**
     * الحصول على مسار الثيم للمطعم
     */
    public static function getThemePath(Restaurant $restaurant, ?string $previewTheme = null): string
    {
        // إذا كان هناك معاينة للثيم، نستخدمه
        if ($previewTheme) {
            return $previewTheme;
        }
        
        return $restaurant->theme->folder_name ?? 'burger-theme';
    }

    /**
     * توليد اسم الـ View ديناميكياً
     */
    public static function view(Restaurant $restaurant, string $viewName, ?string $previewTheme = null): string
    {
        $themePath = self::getThemePath($restaurant, $previewTheme);

        return "themes.{$themePath}.{$viewName}";
    }

    /**
     * جلب قيمة متغير الثيم
     */
    public static function getVar(string $key, $default = null)
    {
        $restaurant = self::getCurrentRestaurant();

        if (! $restaurant) {
            return $default;
        }

        $settings = $restaurant->getThemeSettings();

        return $settings[$key] ?? $default;
    }

    /**
     * جلب المطعم الحالي (من الـ Auth أو من الـ Route)
     */
    private static function getCurrentRestaurant(): ?Restaurant
    {
        // إذا كان المستخدم مسجل دخول (لوحة التحكم)
        if (Auth::check() && Auth::user()->restaurant) {
            return Auth::user()->restaurant;
        }

        // إذا كان في واجهة الزبون (من الـ Route)
        if (request()->route()?->parameter('slug')) {
            return Restaurant::where('slug', request()->route()->parameter('slug'))->first();
        }

        return null;
    }
    
    /**
     * التحقق مما إذا كان المستخدم في وضع المعاينة
     */
    public static function isPreviewMode(): bool
    {
        return request()->has('preview_theme');
    }
    
    /**
     * الحصول على الثيم الحالي للمعاينة
     */
    public static function getPreviewTheme(): ?string
    {
        return request()->query('preview_theme');
    }
}
