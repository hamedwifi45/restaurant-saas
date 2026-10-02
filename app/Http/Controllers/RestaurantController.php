<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Review;
use App\Services\CartService;
use App\Services\RestaurantTableSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RestaurantController extends Controller
{
    public function __construct(
        private readonly RestaurantTableSession $tableSession,
        private readonly CartService $cartService,
    ) {}

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

    public function home(string $slug, Request $request)
    {
        // تحميل المطعم مع الثيم
        $restaurant = Restaurant::with(['theme', 'categories', 'activeOffers'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if ($request->query->has('table')) {
            $table = $this->tableSession->findByIdentifier($restaurant, $request->query('table'));

            if ($table) {
                $this->tableSession->remember($restaurant, $table);
            } else {
                $this->tableSession->forget();
            }

            return redirect()->route('restaurant.menu', $restaurant->slug);
        }

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
    public function menu(string $slug, Request $request)
    {
        $restaurant = Restaurant::with('theme')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if ($request->query->has('table')) {
            $table = $this->tableSession->findByIdentifier($restaurant, $request->query('table'));

            if ($table) {
                $this->tableSession->remember($restaurant, $table);
            } else {
                $this->tableSession->forget();
            }
        }

        // تحميل التصنيفات النشطة مع منتجاتها المتاحة
        $menuCacheKey = "restaurant_menu_{$restaurant->id}";
        $loadMenu = fn (): array => [
            'updated_at' => $restaurant->updated_at?->getTimestamp(),
            'categories' => $restaurant->categories()
                ->where('is_active', true)
                ->with(['products' => function ($query) {
                    $query->where('is_available', true)
                        ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get(),
        ];
        $menu = Cache::remember($menuCacheKey, 3600, $loadMenu);

        if (
            ! is_array($menu)
            || ($menu['updated_at'] ?? null) !== $restaurant->updated_at?->getTimestamp()
            || ! (($menu['categories'] ?? null) instanceof Collection)
        ) {
            Cache::forget($menuCacheKey);
            $menu = Cache::remember($menuCacheKey, 3600, $loadMenu);
        }

        $categories = $menu['categories'];

        $themePath = ThemeHelper::getThemePath($restaurant);

        $table = $this->tableSession->resolve($restaurant);

        $cart = $this->cartService->get($restaurant);
        $cartProducts = Product::where('restaurant_id', $restaurant->id)
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');
        $cartItems = [];
        $cartCount = 0;
        $cartSubtotal = 0;

        foreach ($cart as $productId => $details) {
            $product = $cartProducts->get($productId);
            $quantity = (int) $details['quantity'];

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

        $taxRate = (float) ($restaurant->tax_percentage ?? 0);
        $deliveryFee = $table ? 0 : round((float) ($restaurant->delivery_fee ?? 0), 2);
        $cartTotal = round($cartSubtotal * (1 + ($taxRate / 100)) + $deliveryFee, 2);

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
