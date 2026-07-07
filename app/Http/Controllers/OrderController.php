<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
        public function checkout()
    {
        $cart = session('cart', []);
        if (empty($cart)) return redirect()->route('restaurant.menu', 'abboudi-mongolian');

        // حساب الإجمالي
        $subtotal = 0;
        foreach ($cart as $id => $details) {
            $product = Product::find($id);
            if ($product) $subtotal += $product->price * $details['qty'];
        }
        
        $tax = $subtotal * 0.15;
        $total = $subtotal + $tax;

        return view('themes.burger-theme.checkout', compact('total'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'payment_method' => 'required|in:cash,transfer',
            'notes' => 'nullable|string',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) return redirect()->back();

        // حساب المبالغ
        $subtotal = 0;
        $itemsData = [];
        foreach ($cart as $id => $details) {
            $product = Product::find($id);
            if ($product) {
                $subtotal += $product->price * $details['qty'];
                $itemsData[] = [
                    'product_id' => $id,
                    'quantity' => $details['qty'],
                    'price' => $product->price,
                    'subtotal' => $product->price * $details['qty']
                ];
            }
        }

        $tax = $subtotal * 0.15;
        $finalAmount = $subtotal + $tax;

        // إنشاء الطلب
        $order = Order::create([
            'restaurant_id' => 1, // مؤقتاً، سنحدده ديناميكياً لاحقاً
            'customer_name' => $request->name,
            'customer_phone' => $request->phone,
            'customer_address' => $request->address,
            'total_amount' => $subtotal,
            'final_amount' => $finalAmount,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'status' => 'pending',
            'tracking_code' => 'ORD-' . strtoupper(Str::random(6)), // توليد رمز تتبع
        ]);

        // حفظ عناصر الطلب
        foreach ($itemsData as $item) {
            $item['order_id'] = $order->id;
            Order_item::create($item);
        }

        // تفريغ السلة
        session()->forget('cart');

        return redirect()->route('order.success', $order->tracking_code);
    }

    public function success($code)
    {
        $order = Order::where('tracking_code', $code)->firstOrFail();
        return view('themes.burger-theme.success', compact('order'));
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
