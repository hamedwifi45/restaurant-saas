<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Review;
use Illuminate\Http\Request;


class RestaurantController extends Controller
{
    /**
     * عرض الصفحة الرئيسية للمطعم
     */
    public function home($slug)
    {
        // تحميل المطعم مع الثيم
        $restaurant = Restaurant::with(['theme', 'categories', 'activeOffers'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        $categories = $restaurant->categories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(6) 
            ->get();

        // تحديد مسار الثيم (سنستخدم burger-theme كافتراضي حالياً)
        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.pages.home", compact('restaurant' , 'categories'));
    }
    public function showProduct($slug, $productId)
{
    $restaurant = Restaurant::with('theme')
        ->where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    $product = Product::where('id', $productId)
        ->where('restaurant_id', $restaurant->id)
        ->where('is_available', true)
        ->firstOrFail();

    // جلب التقييمات المعتمدة للمنتج
    $reviews = Review::whereHas('order', function($query) use ($productId) {
            $query->whereHas('items', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        })
        ->with('images')
        ->latest()
        ->paginate(10);

    $themePath = ThemeHelper::getThemePath($restaurant);

    return view("themes.{$themePath}.pages.product", compact('restaurant', 'product', 'reviews'));
}
    /**
     * عرض قائمة الطعام (سنضيفها في المرحلة 4.2)
     */
    public function menu($slug)
    {
        $restaurant = Restaurant::with('theme')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // تحميل التصنيفات النشطة مع منتجاتها المتاحة
        $categories = $restaurant->categories()
            ->where('is_active', true)
            ->with(['products' => function($query) {
                $query->where('is_available', true)
                      ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.pages.menu", compact('restaurant', 'categories'));
    }
}
