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

        if (isset($cart[$productId])) {
            $cart[$productId]['qty'] += $quantity;
        } else {
            $cart[$productId] = ["qty" => $quantity];
        }

        session(['cart' => $cart]);

        // --- إضافة جديدة: بناء محتوى السلة المصغرة لإرجاعه للواجهة ---
        $total = 0;
        $cartHtml = '';
        $count = 0;

        foreach ($cart as $id => $details) {
            $product = \App\Models\Product::find($id);
            if ($product) {
                $count += $details['qty'];
                $total += $product->price * $details['qty'];
                $imageUrl = $product->image ? asset('storage/'.$product->image) : 'https://via.placeholder.com/50';
                
                $cartHtml .= '
                    <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition">
                        <img src="'.$imageUrl.'" class="w-12 h-12 rounded-md object-cover">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-800 truncate">'.$product->name.'</h4>
                            <p class="text-xs text-primary font-bold">'.$product->price.' ر.س × '.$details['qty'].'</p>
                        </div>
                    </div>
                ';
            }
        }

        if ($count === 0) {
            $cartHtml = '<div class="text-center py-8 text-gray-400 text-sm">السلة فارغة حالياً 🍽️</div>';
        }
        // -------------------------------------------------------------

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة المنتج للسلة بنجاح! 🛒',
                'count' => $count,
                'total' => number_format($total, 2),
                'cart_html' => $cartHtml
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
