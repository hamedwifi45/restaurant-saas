<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index($slug)
    {
        // جلب بيانات المطعم لعرضها في الهيدر والفوتر
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        
        // جلب محتويات السلة من الجلسة
        $cart = session('cart', []);
        $products = [];
        $total = 0;

        // حساب تفاصيل كل منتج في السلة
        foreach ($cart as $id => $details) {
            $product = Product::find($id);
            if ($product) {
                $products[] = [
                    'product' => $product,
                    'qty' => $details['qty'],
                    'price' => $product->price,
                    'subtotal' => $product->price * $details['qty']
                ];
                $total += $product->price * $details['qty'];
            }
        }

        return view('themes.burger-theme.cart', compact('restaurant', 'products', 'total'));
    }

    /**
     * إضافة منتج للسلة
     */
    public function add(Request $request, $slug)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = session('cart', []);
        $productId = $request->product_id;
        $quantity = $request->quantity;

        // إذا كان المنتج موجوداً مسبقاً نزيد الكمية، وإلا نضيفه كعنصر جديد
        if (isset($cart[$productId])) {
            $cart[$productId]['qty'] += $quantity;
        } else {
            $cart[$productId] = [
                "qty" => $quantity
            ];
        }

        session(['cart' => $cart]);

        // دعم الطلبات عبر AJAX لإظهار التنبيهات دون إعادة تحميل
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة المنتج للسلة بنجاح! 🛒',
                'count' => count($cart)
            ]);
        }

        return redirect()->back()->with('success', 'تمت إضافة المنتج للسلة بنجاح! 🛒');
    }

    /**
     * تحديث كمية منتج في السلة
     */
    public function update(Request $request, $slug)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = session('cart', []);
        
        if(isset($cart[$request->product_id])) {
            $cart[$request->product_id]['qty'] = $request->quantity;
            session(['cart' => $cart]);
        }

        return redirect()->route('cart.index', $slug)->with('success', 'تم تحديث كمية المنتج');
    }

    /**
     * حذف منتج من السلة
     */
    public function remove(Request $request, $slug)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $cart = session('cart', []);
        
        if(isset($cart[$request->product_id])) {
            unset($cart[$request->product_id]);
            session(['cart' => $cart]);
        }

        return redirect()->route('cart.index', $slug)->with('success', 'تم حذف المنتج من السلة');
    }
}
