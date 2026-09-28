<?php

namespace App\Observers;

use App\Models\RestaurantThemeSetting;
use Illuminate\Support\Facades\Cache;

class RestaurantThemeSettingObserver
{
    /**
     * يتم تنفيذ هذه الدالة فور حفظ أو تحديث الإعدادات في الفلمنت
     */
    public function saved(RestaurantThemeSetting $setting): void
    {
        if ($setting->restaurant_id) {
            Cache::forget("restaurant_theme_settings_{$setting->restaurant_id}");
        }
    }

    /**
     * يتم تنفيذ هذه الدالة عند الحذف
     */
    public function deleted(RestaurantThemeSetting $setting): void
    {
        if ($setting->restaurant_id) {
            Cache::forget("restaurant_theme_settings_{$setting->restaurant_id}");
        }
    }
}