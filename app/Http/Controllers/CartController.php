<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Product;
use App\Models\Restaurant;
use App\Services\CartService;
use App\Services\RestaurantTableSession;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            ->whereIn('id', collect($cart)->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $products = [];
        $total = 0;
        foreach ($cart as $cartKey => $details) {
            $product = $cartProducts->get($details['product_id']);

            if ($product) {
                $price = (float) ($details['unit_price'] ?? $product->price);
                $subtotal = $price * $details['quantity'];

                $products[] = [
                    'cart_key' => $cartKey,
                    'product' => $product,
                    'qty' => $details['quantity'],
                    'price' => $price,
                    'variant_name' => $details['variant_name'] ?? null,
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }
        }

        $themeName = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themeName}.cart.cart", compact('restaurant', 'products', 'total', 'deliveryFee'));
    }

    /**
     * إضافة منتج للسلة
     */
    /**
     * إضافة منتج للسلة (دعم الأسعار المفتوحة والأوزان)
     */
    public function add(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        if (! $restaurant->canAcceptOrders()) {
            $message = $restaurant->orderUnavailableMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 409);
            }

            return redirect()->back()->with('error', $message);
        }

        // 1. التحقق من المدخلات
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'custom_amount' => 'nullable|numeric|min:0',
            'variant_index' => 'nullable|integer',
            'options' => 'sometimes|array',
        ]);

        // 2. جلب المنتج المتاح
        $product = Product::where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->findOrFail($request->product_id);

        $unitPrice = (float) $product->price;
        $selectedVariantName = null;

        // 3. تحديد السعر بناءً على نوع المنتج (سعر مفتوح / أوزان / سعر ثابت)
        if ($product->price_type === 'open_price') {
            $customAmount = $request->input('custom_amount', $product->min_price ?? $product->price);
            if (! is_numeric($customAmount)) {
                throw ValidationException::withMessages([
                    'custom_amount' => 'يرجى إدخال مبلغ صالح.',
                ]);
            }

            $unitPrice = (float) $customAmount;
            if (($product->min_price !== null && $unitPrice < (float) $product->min_price)
                || ($product->max_price !== null && $unitPrice > (float) $product->max_price)) {
                throw ValidationException::withMessages([
                    'custom_amount' => 'المبلغ المدخل خارج النطاق المسموح.',
                ]);
            }
        } elseif ($product->price_type === 'variant') {
            $variants = is_array($product->variants) ? $product->variants : [];
            $variantIndex = $request->integer('variant_index');
            $selectedVariant = $variants[$variantIndex] ?? null;

            if (! is_array($selectedVariant)
                || ! isset($selectedVariant['name'], $selectedVariant['price'])
                || ! is_numeric($selectedVariant['price'])) {
                throw ValidationException::withMessages([
                    'variant_index' => 'يرجى اختيار خيار صالح.',
                ]);
            }

            $unitPrice = (float) $selectedVariant['price'];
            $selectedVariantName = (string) $selectedVariant['name'];
        }

        // 4. إنشاء مفتاح فريد لعنصر السلة لدعم تعدد الخيارات
        $cartKey = (string) $product->id;
        if ($selectedVariantName !== null) {
            $cartKey .= '_v_'.$request->input('variant_index');
        } elseif ($product->price_type === 'open_price') {
            $cartKey .= '_op_'.$unitPrice;
        }

        $cart = $this->cartService->get($restaurant);
        $quantity = (int) $request->quantity;

        // 5. حفظ البيانات في السلة
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => (int) $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'variant_name' => $selectedVariantName,
                'price_type' => $product->price_type,
                'options' => $request->input('options', []),
            ];
        }

        $this->cartService->put($restaurant, $cart);

        // 6. جلب بيانات المنتجات لحساب الإجمالي المحدث
        $productIds = collect($cart)->pluck('product_id')->unique();
        $cartProducts = Product::where('restaurant_id', $restaurant->id)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // 7. بناء الاستجابة ومحتوى السلة المصغرة
        $total = 0;
        $count = 0;
        $items = [];

        foreach ($cart as $key => $details) {
            $cartProduct = $cartProducts->get($details['product_id']);
            if ($cartProduct) {
                $itemPrice = (float) ($details['unit_price'] ?? $cartProduct->price);
                $itemQuantity = (int) $details['quantity'];
                $itemSubtotal = $itemPrice * $itemQuantity;

                $count += $itemQuantity;
                $total += $itemSubtotal;

                $itemName = $cartProduct->name;
                if (! empty($details['variant_name'])) {
                    $itemName .= ' ('.$details['variant_name'].')';
                }

                $items[] = [
                    'cart_key' => $key,
                    'name' => $itemName,
                    'quantity' => $itemQuantity,
                    'price' => (float) $itemPrice,
                    'subtotal' => (float) $itemSubtotal,
                ];
            }
        }

        $deliveryFee = $this->deliveryFeeFor($restaurant);
        $tax = round($total * (float) ($restaurant->tax_percentage ?? 0) / 100, 2);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة المنتج للسلة بنجاح! 🛒',
                'count' => $count,
                'subtotal' => $total,
                'total' => $total,
                'tax_amount' => $tax,
                'delivery_fee' => $deliveryFee,
                'grand_total' => $total + $tax,
                'items' => $items,
            ]);
        }

        return redirect()->back()->with('success', 'تمت إضافة المنتج للسلة بنجاح! 🛒');
    }

    /**
     * تحديث كمية منتج في السلة
     */
    /**
     * تحديث كمية منتج في السلة (دعم متوافق للأسعار الثابتة والمفتوحة والأوزان)
     */
    public function update(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        if ($request->has('items')) {
            $validated = $request->validate([
                'items' => ['required', 'array', 'min:1'],
                'items.*.cart_key' => ['nullable', 'string', 'max:100'],
                'items.*.product_id' => ['nullable', 'integer'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
            ]);

            $cart = $this->cartService->get($restaurant);
            $updatedItems = [];

            foreach ($validated['items'] as $item) {
                $cartKey = $item['cart_key'] ?? null;
                $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;

                // إذا لم يوجد cart_key مباشر، نحاول مطابقته عبر product_id في السلة
                if (($cartKey === null || ! isset($cart[$cartKey])) && $productId !== null) {
                    foreach ($cart as $key => $details) {
                        if ((int) $details['product_id'] === $productId) {
                            $cartKey = $key;
                            break;
                        }
                    }
                }

                if ($cartKey === null || ! isset($cart[$cartKey])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'تم تغيير محتويات السلة أو المنتج غير موجود. يرجى تحديث الصفحة.',
                    ], 409);
                }

                $cart[$cartKey]['quantity'] = (int) $item['quantity'];
                $updatedItems[] = [
                    'cart_key' => $cartKey,
                    'product_id' => $cart[$cartKey]['product_id'],
                    'quantity' => $cart[$cartKey]['quantity'],
                ];
            }

            $this->cartService->put($restaurant, $cart);

            return response()->json([
                'success' => true,
                'items' => $updatedItems,
            ]);
        }

        // التعامل مع الطلب الفردي (Single Request)
        $validated = $request->validate([
            'cart_key' => ['nullable', 'string', 'max:100'],
            'product_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->cartService->get($restaurant);
        $cartKey = $validated['cart_key'] ?? null;
        $productId = isset($validated['product_id']) ? (int) $validated['product_id'] : null;

        if (($cartKey === null || ! isset($cart[$cartKey])) && $productId !== null) {
            foreach ($cart as $key => $details) {
                if ((int) $details['product_id'] === $productId) {
                    $cartKey = $key;
                    break;
                }
            }
        }

        if ($cartKey === null || ! isset($cart[$cartKey])) {
            return response()->json([
                'success' => false,
                'message' => 'العنصر غير موجود في السلة.',
            ], 409);
        }

        $product = Product::where('restaurant_id', $restaurant->id)
            ->findOrFail($cart[$cartKey]['product_id']);

        $cart[$cartKey]['quantity'] = (int) $validated['quantity'];
        $this->cartService->put($restaurant, $cart);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'product_id' => $product->id,
                'cart_key' => $cartKey,
                'quantity' => $cart[$cartKey]['quantity'],
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
        $validated = $request->validate([
            'cart_key' => ['nullable', 'string', 'max:100', 'required_without:product_id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id', 'required_without:cart_key'],
        ]);

        $cart = $this->cartService->get($restaurant);
        $cartKey = $validated['cart_key'] ?? null;

        if ($cartKey === null && isset($validated['product_id'])) {
            $productId = (int) $validated['product_id'];
            $cartKey = array_key_first(array_filter(
                $cart,
                static fn (array $details): bool => $details['product_id'] === $productId
            ));
        }

        if ($cartKey === null || ! isset($cart[$cartKey])) {
            return response()->json([
                'success' => false,
                'message' => 'تم تغيير محتويات السلة. يرجى تحديث الصفحة.',
            ], 409);
        }

        $product = Product::where('restaurant_id', $restaurant->id)
            ->findOrFail($cart[$cartKey]['product_id']);
        unset($cart[$cartKey]);
        $this->cartService->put($restaurant, $cart);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'product_id' => $product->id,
                'cart_key' => $cartKey,
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
