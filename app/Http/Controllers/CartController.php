<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Product;
use App\Models\Restaurant;
use App\Services\CartService;
use App\Services\RestaurantTableSession;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly RestaurantTableSession $tableSession,
    ) {}

    /**
     * عرض محتويات السلة
     */
    public function index($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $cart = $this->cartService->get($restaurant);
        $deliveryFee = $this->deliveryFeeFor($restaurant);
        $cartProducts = Product::where('restaurant_id', $restaurant->id)
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $products = [];
        $total = 0;

        foreach ($cart as $id => $details) {
            $product = $cartProducts->get($id);

            if ($product) {
                $products[] = [
                    'product' => $product,
                    'qty' => $details['quantity'],
                    'price' => $product->price,
                    'subtotal' => $product->price * $details['quantity'],
                ];
                $total += $product->price * $details['quantity'];
            }
        }

        $themeName = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themeName}.cart.cart", compact('restaurant', 'products', 'total', 'deliveryFee'));
    }

    /**
     * إضافة منتج للسلة
     */
    public function add(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'options' => 'sometimes|array',
        ]);

        // التأكد أن المنتج ينتمي للمطعم الحالي ومتاح
        $product = Product::where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->findOrFail($request->product_id);

        // 3. استخدام مفتاح الجلسة الخاص بهذا المطعم فقط
        $cart = $this->cartService->get($restaurant);

        // (تم حذف التحقق من وجود مطاعم أخرى لأنه لم يعد ضرورياً مع فصل جلسات السلة)

        $productId = $product->id;
        $quantity = $request->quantity;

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => (int) $productId,
                'quantity' => (int) $quantity,
                'options' => $request->input('options', []),
            ];
        }

        $this->cartService->put($restaurant, $cart);

        $cartProducts = Product::where('restaurant_id', $restaurant->id)
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        // --- بناء محتوى السلة المصغرة ---
        $total = 0;
        $cartHtml = '';
        $count = 0;

        foreach ($cart as $id => $details) {
            $cartProduct = $cartProducts->get($id);
            if ($cartProduct) {
                $count += $details['quantity'];
                $total += $cartProduct->price * $details['quantity'];
                $imageUrl = $cartProduct->image ? asset('storage/'.$cartProduct->image) : 'https://via.placeholder.com/50';

                $cartHtml .= '
                    <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition">
                        <img src="'.$imageUrl.'" loading="lazy" decoding="async" class="w-12 h-12 rounded-md object-cover">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-800 truncate">'.$cartProduct->name.'</h4>
                            <p class="text-xs text-primary font-bold">'.$cartProduct->price.' ل.س × '.$details['quantity'].'</p>
                        </div>
                    </div>
                ';
            }
        }

        if ($count === 0) {
            $cartHtml = '<div class="text-center py-8 text-gray-400 text-sm">السلة فارغة حالياً 🍽️</div>';
        }

        if ($request->wantsJson() || $request->ajax()) {
            $items = collect($cart)
                ->map(function (array $details, $id) use ($cartProducts): ?array {
                    $product = $cartProducts->get($id);

                    if (! $product) {
                        return null;
                    }

                    $quantity = (int) $details['quantity'];

                    return [
                        'name' => $product->name,
                        'quantity' => $quantity,
                        'price' => (float) $product->price,
                        'subtotal' => (float) ($product->price * $quantity),
                    ];
                })
                ->filter()
                ->values();

            $deliveryFee = $this->deliveryFeeFor($restaurant);

            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة المنتج للسلة بنجاح! 🛒',
                'count' => $count,
                'subtotal' => $total,
                'total' => $total,
                'delivery_fee' => $deliveryFee,
                'grand_total' => round(
                    $total
                    + ($total * ((float) ($restaurant->tax_percentage ?? 0) / 100))
                    + $deliveryFee,
                    2
                ),
                'items' => $items,
            ]);
        }

        return redirect()->back()->with('success', 'تمت إضافة المنتج للسلة بنجاح! 🛒');
    }

    /**
     * تحديث كمية منتج في السلة
     */
    public function update(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // التأكد من أن المنتج ينتمي للمطعم
        $product = Product::where('restaurant_id', $restaurant->id)->findOrFail($request->product_id);

        $cart = $this->cartService->get($restaurant);

        if (isset($cart[$request->product_id])) {
            $cart[$request->product_id]['quantity'] = (int) $request->quantity;
            $this->cartService->put($restaurant, $cart);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'product_id' => $product->id,
                'quantity' => (int) ($cart[$product->id]['quantity'] ?? $request->quantity),
            ]);
        }

        return redirect()->route('cart.index', $slug)->with('success', 'تم تحديث كمية المنتج');
    }

    /**
     * حذف منتج من السلة
     */
    public function remove(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::where('restaurant_id', $restaurant->id)->findOrFail($request->product_id);

        $cart = $this->cartService->get($restaurant);

        if (isset($cart[$request->product_id])) {
            unset($cart[$request->product_id]);
            $this->cartService->put($restaurant, $cart);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'product_id' => $product->id,
            ]);
        }

        return redirect()->route('cart.index', $slug)->with('success', 'تم حذف المنتج من السلة');
    }

    private function deliveryFeeFor(Restaurant $restaurant): float
    {
        if ($this->tableSession->resolve($restaurant)) {
            return 0;
        }

        return round((float) ($restaurant->delivery_fee ?? 0), 2);
    }
}
