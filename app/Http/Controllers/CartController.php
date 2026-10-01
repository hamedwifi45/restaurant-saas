<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * عرض محتويات السلة
     */
    public function index($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        // 1. استخدام مفتاح جلسة فريد لكل مطعم
        $sessionKey = 'cart_'.$restaurant->id;
        $cart = session($sessionKey, []);

        $products = [];
        $total = 0;

        foreach ($cart as $id => $details) {
            // 2. جلب المنتج مباشرة (يمكن إضافة شرط للتأكد من أنه ينتمي للمطعم كأمان إضافي)
            $product = Product::find($id);

            // تأكد أن المنتج موجود وأنه ينتمي لهذا المطعم تحديداً
            if ($product && $product->restaurant_id == $restaurant->id) {
                $products[] = [
                    'product' => $product,
                    'qty' => $details['qty'],
                    'price' => $product->price,
                    'subtotal' => $product->price * $details['qty'],
                ];
                $total += $product->price * $details['qty'];
            }
        }

        $themeName = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themeName}.cart.cart", compact('restaurant', 'products', 'total'));
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
        ]);

        // التأكد أن المنتج ينتمي للمطعم الحالي ومتاح
        $product = Product::where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->findOrFail($request->product_id);

        // 3. استخدام مفتاح الجلسة الخاص بهذا المطعم فقط
        $sessionKey = 'cart_'.$restaurant->id;
        $cart = session($sessionKey, []);

        // (تم حذف التحقق من وجود مطاعم أخرى لأنه لم يعد ضرورياً مع فصل جلسات السلة)

        $productId = $product->id;
        $quantity = $request->quantity;

        if (isset($cart[$productId])) {
            $cart[$productId]['qty'] += $quantity;
        } else {
            $cart[$productId] = ['qty' => $quantity];
        }

        // حفظ السلة الخاصة بالمطعم فقط
        session([$sessionKey => $cart]);

        // --- بناء محتوى السلة المصغرة ---
        $total = 0;
        $cartHtml = '';
        $count = 0;

        foreach ($cart as $id => $details) {
            $cartProduct = Product::find($id);
            if ($cartProduct && $cartProduct->restaurant_id == $restaurant->id) {
                $count += $details['qty'];
                $total += $cartProduct->price * $details['qty'];
                $imageUrl = $cartProduct->image ? asset('storage/'.$cartProduct->image) : 'https://via.placeholder.com/50';

                $cartHtml .= '
                    <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition">
                        <img src="'.$imageUrl.'" class="w-12 h-12 rounded-md object-cover">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-800 truncate">'.$cartProduct->name.'</h4>
                            <p class="text-xs text-primary font-bold">'.$cartProduct->price.' ر.س × '.$details['qty'].'</p>
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
                ->map(function (array $details, $id) use ($restaurant): ?array {
                    $product = Product::where('restaurant_id', $restaurant->id)->find($id);

                    if (! $product) {
                        return null;
                    }

                    $quantity = (int) ($details['qty'] ?? 0);

                    return [
                        'name' => $product->name,
                        'quantity' => $quantity,
                        'price' => (float) $product->price,
                        'subtotal' => (float) ($product->price * $quantity),
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة المنتج للسلة بنجاح! 🛒',
                'count' => $count,
                'subtotal' => $total,
                'total' => $total,
                'grand_total' => $total * (1 + ((float) ($restaurant->tax_percentage ?? 0) / 100)),
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
        $sessionKey = 'cart_'.$restaurant->id;

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // التأكد من أن المنتج ينتمي للمطعم
        $product = Product::where('restaurant_id', $restaurant->id)->findOrFail($request->product_id);

        $cart = session($sessionKey, []);

        if (isset($cart[$request->product_id])) {
            $cart[$request->product_id]['qty'] = $request->quantity;
            session([$sessionKey => $cart]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'product_id' => $product->id,
                'quantity' => (int) $cart[$product->id]['qty'],
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
        $sessionKey = 'cart_'.$restaurant->id;

        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::where('restaurant_id', $restaurant->id)->findOrFail($request->product_id);

        $cart = session($sessionKey, []);

        if (isset($cart[$request->product_id])) {
            unset($cart[$request->product_id]);
            session([$sessionKey => $cart]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'product_id' => $product->id,
            ]);
        }

        return redirect()->route('cart.index', $slug)->with('success', 'تم حذف المنتج من السلة');
    }
}
