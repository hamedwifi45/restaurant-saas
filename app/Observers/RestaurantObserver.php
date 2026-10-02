<?php

namespace App\Observers;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

class RestaurantObserver
{
    /**
     * يتم تنفيذ هذه الدالة تلقائياً بمجرد تحديث بيانات المطعم
     */
    public function updated(Restaurant $restaurant): void
    {
        // مسح كاش إعدادات هذا المطعم المحدد فقط فور الحفظ
        if ($restaurant->wasChanged('theme_settings')) {
            Cache::forget("restaurant_theme_settings_{$restaurant->id}");
        }
    }

    public function saved(Restaurant $restaurant): void
    {
        // إذا تم تعديل حقل theme_settings أو عند أي عملية حفظ للإعدادات
        if ($restaurant->wasChanged('theme_settings') || $restaurant->wasChanged()) {
            Cache::forget("restaurant_theme_settings_{$restaurant->id}");
        }

        Cache::forget("restaurant_menu_{$restaurant->id}");
    }

    public function deleted(Restaurant $restaurant): void
    {
        Cache::forget("restaurant_theme_settings_{$restaurant->id}");
        Cache::forget("restaurant_menu_{$restaurant->id}");
    }
}
