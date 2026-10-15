<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Review;
use App\Services\CartService;
use App\Services\RestaurantTableSession;
use Illuminate\Database\Eloquent\Builder;
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

    public function showProduct($slug, $slugP)
    {
        $restaurant = Restaurant::with('theme')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $product = Product::with(['category', 'images'])
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->where(function (Builder $query) use ($slugP): void {
                $query->where('slug', $slugP);

                $productId = filter_var($slugP, FILTER_VALIDATE_INT);
                if ($productId !== false) {
                    $query->orWhere('id', $productId);
                }
            })
            ->firstOrFail();

        $productImages = $product->images
            ->sortByDesc('is_primary')
            ->map(fn ($image) => [
                'url' => $image->url,
                'alt' => $image->alt ?: $product->name,
            ])
            ->values();

        if ($product->image) {
            $legacyImageUrl = media_url($product->image);

            if (! $productImages->contains('url', $legacyImageUrl)) {
                $productImages->push([
                    'url' => $legacyImageUrl,
                    'alt' => $product->name,
                ]);
            }
        }

        if ($productImages->isEmpty() && $product->category?->image) {
            $productImages->push([
                'url' => media_url($product->category->image),
                'alt' => $product->category->name,
            ]);
        }

        $relatedProducts = $product->category_id
            ? Product::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('category_id', $product->category_id)
                ->where('is_available', true)
                ->where('id', '!=', $product->id)
                ->orderBy('sort_order')
                ->limit(4)
                ->get()
            : collect();

        $reviewsQuery = Review::where('restaurant_id', $restaurant->id)
            ->whereHas('order', function ($query) use ($restaurant, $product) {
                $query->where('restaurant_id', $restaurant->id)
                    ->whereHas('items', function ($itemsQuery) use ($product) {
                        $itemsQuery->where('product_id', $product->id);
                    });
            });
        $reviewCount = (clone $reviewsQuery)->count();
        $reviewAverage = (float) ((clone $reviewsQuery)->avg('rating') ?? 0);
        $reviews = $reviewsQuery
            ->with('images')
            ->latest()
            ->paginate(10);

        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.pages.product", compact(
            'restaurant',
            'product',
            'reviews',
            'reviewCount',
            'reviewAverage',
            'productImages',
            'relatedProducts',
        ));
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

        // 1. تحميل التصنيفات والمنتجات من الكاش
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

        $allCategories = $menu['categories'] ?? collect();

        // جهّز مسار الصورة لكل تصنيف للابتعاد عن استخدام @php داخل Blade
        $allCategories->transform(function ($cat) {
            if (! empty($cat->image)) {
                $cat->image_path = asset('storage/'.$cat->image);
            } elseif (! empty($cat->image_url)) {
                $cat->image_path = $cat->image_url;
            } else {
                $cat->image_path = null;
            }

            return $cat;
        });

        // 2. تصفية التصنيفات في حال تم اختيار تصنيف معين
        $selectedCategorySlug = $request->query('category');
        if ($selectedCategorySlug) {
            $categories = $allCategories->where('slug', $selectedCategorySlug);
        } else {
            $categories = $allCategories;
        }

        $themePath = ThemeHelper::getThemePath($restaurant);
        $table = $this->tableSession->resolve($restaurant);

        // 3. حساب تفاصيل السلة
        $cart = $this->cartService->get($restaurant);
        $cartProductIds = collect($cart)->pluck('product_id')->unique();
        $cartProducts = Product::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $cartProductIds)
            ->get()
            ->keyBy('id');

        $cartItems = [];
        $cartCount = 0;
        $cartSubtotal = 0;

        foreach ($cart as $details) {
            $product = $cartProducts->get($details['product_id']);
            $quantity = (int) ($details['quantity'] ?? 0);

            if ($product && $quantity > 0) {
                $unitPrice = (float) ($details['unit_price'] ?? $product->price);
                $itemSubtotal = $unitPrice * $quantity;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'variant_name' => $details['variant_name'] ?? null,
                    'subtotal' => $itemSubtotal,
                ];
                $cartCount += $quantity;
                $cartSubtotal += $itemSubtotal;
            }
        }

        $cartTax = round($cartSubtotal * (float) ($restaurant->tax_percentage ?? 0) / 100, 2);
        $cartDeliveryFee = $table ? 0.0 : round((float) ($restaurant->delivery_fee ?? 0), 2);
        $cartTotal = round($cartSubtotal + $cartTax + $cartDeliveryFee, 2);

        return view("themes.{$themePath}.pages.menu", compact(
            'restaurant',
            'categories',
            'allCategories',
            'selectedCategorySlug',
            'table',
            'cartItems',
            'cartCount',
            'cartSubtotal',
            'cartTax',
            'cartDeliveryFee',
            'cartTotal'
        ));
    }
}
