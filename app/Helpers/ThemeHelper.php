<?php

namespace App\Helpers;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class ThemeHelper
{
    /**
     * الحصول على مسار الثيم للمطعم
     */
    public static function getThemePath(Restaurant $restaurant): string
    {
        $folderName = $restaurant->theme?->folder_name;

        return is_string($folderName)
            && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $folderName) === 1
            && is_dir(resource_path("views/themes/{$folderName}"))
            ? $folderName
            : 'albasha-burger';
    }

    /**
     * توليد اسم الـ View ديناميكياً
     */
    public static function view(Restaurant $restaurant, string $viewName): string
    {
        $themePath = self::getThemePath($restaurant);

        return "themes.{$themePath}.{$viewName}";
    }

    public static function viewOrFallback(Restaurant $restaurant, string $viewName, string $fallbackView): string
    {
        $themeView = self::view($restaurant, $viewName);

        return View::exists($themeView) ? $themeView : $fallbackView;
    }

    public static function layoutView(Restaurant $restaurant): string
    {
        return self::viewOrFallback($restaurant, 'layout', 'themes.albasha-burger.layout');
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
}
