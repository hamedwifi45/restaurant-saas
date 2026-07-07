<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);
        $products = [];
        $total = 0;

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
        $restaurant = Restaurant::where('subdomain', request()->getHost())->first() ?? Restaurant::first();

        return view('themes.burger-theme.cart', compact('restaurant','products', 'total'));
    }
    public function add(Request $request)
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
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = session('cart', []);
        $cart[$request->product_id]['qty'] = $request->quantity;
        
        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'تم تحديث السلة');
    }

    /**
     * حذف منتج من السلة
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $cart = session('cart', []);
        unset($cart[$request->product_id]);
        
        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'تم حذف المنتج من السلة');
    }
}
