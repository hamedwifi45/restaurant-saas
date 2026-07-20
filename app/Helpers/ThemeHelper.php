<?php

namespace App\Helpers;

use App\Models\Restaurant;

class ThemeHelper
{
    /**
     * الحصول على مسار الثيم للمطعم
     */
    public static function getThemePath(Restaurant $restaurant): string
    {
        return $restaurant->theme->folder_name ?? 'burger-theme';
    }

    /**
     * توليد اسم الـ View ديناميكياً
     */
    public static function view(Restaurant $restaurant, string $viewName): string
    {
        $themePath = self::getThemePath($restaurant);
        return "themes.{$themePath}.{$viewName}";
    }
}