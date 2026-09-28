<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Review;

class RestaurantController extends Controller
{
    /**
     * عرض الصفحة الرئيسية للمطعم
     */
    public function index()
    {
        $restaurant = auth()->user()->restaurant;

        // جلب الإعدادات من قاعدة البيانات، أو استخدام القيم الافتراضية
        $settings = $restaurant->themeSettings?->settings ?? $restaurant->theme?->default_settings ?? [];
        return view('restaurant.dashboard', compact('settings'));
    }

    public function home(string $slug)
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
        request()->attributes->set('current_restaurant', $restaurant);
        // تحديد مسار الثيم (سنستخدم burger-theme كافتراضي حالياً)
        $themePath = ThemeHelper::getThemePath($restaurant);
        
        return view("themes.{$themePath}.pages.home", compact('restaurant', 'categories'));
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
        $reviews = Review::whereHas('order', function ($query) use ($productId) {
            $query->whereHas('items', function ($q) use ($productId) {
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
            ->with(['products' => function ($query) {
                $query->where('is_available', true)
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        $themePath = ThemeHelper::getThemePath($restaurant);

        $table = session('restaurant_table_restaurant_id') === $restaurant->id
            ? $restaurant->tables()->find(session('restaurant_table_id'))
            : null;

        $cart = session('cart_'.$restaurant->id, []);
        $cartProducts = Product::where('restaurant_id', $restaurant->id)
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');
        $cartItems = [];
        $cartCount = 0;
        $cartSubtotal = 0;

        foreach ($cart as $productId => $details) {
            $product = $cartProducts->get($productId);
            $quantity = (int) ($details['qty'] ?? 0);

            if ($product && $quantity > 0) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ];
                $cartCount += $quantity;
                $cartSubtotal += $product->price * $quantity;
            }
        }

        $cartTotal = $cartSubtotal * 1.15;

        return view("themes.{$themePath}.pages.menu", compact(
            'restaurant',
            'categories',
            'table',
            'cartItems',
            'cartCount',
            'cartSubtotal',
            'cartTotal',
        ));
    }
}
