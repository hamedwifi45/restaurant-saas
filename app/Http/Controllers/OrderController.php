<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function track($slug, $code)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('tracking_code', $code)->firstOrFail();
        
        return view('themes.burger-theme.track', compact('restaurant', 'order'));
    }
        public function checkout($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $cart = session('cart', []);
        
        // إذا كانت السلة فارغة نعيد المستخدم للقائمة
        if (empty($cart)) {
            return redirect()->route('restaurant.menu', $slug)->with('error', 'السلة فارغة');
        }

        // حساب الإجمالي والضريبة
        $subtotal = 0;
        foreach ($cart as $id => $details) {
            $product = Product::find($id);
            if ($product) {
                $subtotal += $product->price * $details['qty'];
            }
        }
        
        $tax = $subtotal * 0.15;
        $total = $subtotal + $tax;

        return view('themes.burger-theme.checkout', compact('restaurant', 'total' , 'slug'));
    }

    /**
     * حفظ الطلب الجديد
     */
    public function store(Request $request, $slug)
{
    $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
    
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'address' => 'required|string',
        'payment_method' => 'required|in:cash,transfer',
        'notes' => 'nullable|string',
    ]);

    $cart = session('cart', []);
    if (empty($cart)) {
        return redirect()->back()->with('error', 'لا يمكن إتمام طلب فارغ');
    }

    $subtotal = 0;
    $itemsData = [];
    
    // تجهيز بيانات العناصر وحساب المجموع
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
    $deliveryFee = $restaurant->delivery_fee ?? 0;
    $discount = 0; // يمكن تغييره لاحقاً عند تطبيق العروض
    $finalAmount = $subtotal + $tax + $deliveryFee - $discount;

    // إنشاء سجل الطلب الرئيسي
    $order = Order::create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => $request->name,
        'customer_phone' => $request->phone,
        'delivery_address' => $request->address, // ← تم التصحيح
        'delivery_type' => 'delivery', // أو 'pickup' حسب الحاجة
        'delivery_city' => $restaurant->city, // أو من النموذج
        'delivery_fee' => $deliveryFee,
        'subtotal' => $subtotal, // ← تمت الإضافة
        'discount' => $discount, // ← تمت الإضافة
        'total_amount' => $subtotal,
        'final_amount' => $finalAmount,
        'payment_method' => $request->payment_method,
        'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'pending',
        'notes' => $request->notes,
        'status' => 'pending',
        'tracking_code' => 'ORD-' . strtoupper(Str::random(6)),
    ]);

    // حفظ عناصر الطلب في جدول order_items
    foreach ($itemsData as $item) {
    $product = Product::find($item['product_id']);
    
    Order_item::create([
        'order_id' => $order->id,
        'product_id' => $item['product_id'],
        'product_name' => $product->name, // ← تمت الإضافة
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'total' => $item['subtotal'], // ← تم تغيير الاسم من subtotal إلى total
    ]);
}

    // إنشاء فاتورة تلقائية
    $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);

    Invoice::create([
        'order_id' => $order->id,
        'restaurant_id' => $order->restaurant_id,
        'invoice_number' => $invoiceNumber,
        'subtotal' => $subtotal,
        'tax_amount' => $tax,
        'total_amount' => $finalAmount,
        'status' => 'pending',
    ]);

    // تفريغ السلة بعد نجاح الطلب
    session()->forget('cart');

    return redirect()->route('order.success', [$slug, $order->tracking_code]);
}
    /**
 * صفحة إدخال رمز التتبع
 */
public function trackForm($slug)
{
    $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
    return view('themes.burger-theme.track-form', compact('restaurant'));
}

/**
 * معالجة نموذج إدخال رمز التتبع
 */
public function trackSearch(Request $request, $slug)
{
    $request->validate([
        'tracking_code' => 'required|string'
    ]);

    $code = strtoupper(trim($request->tracking_code));
    
    $order = Order::where('tracking_code', $code)->first();
    
    if (!$order) {
        return redirect()->back()->with('error', 'رمز التتبع غير صحيح. تأكد من الرمز وحاول مرة أخرى.');
    }

    return redirect()->route('order.track', [$slug, $order->tracking_code]);
}
    /**
 * صفحة نجاح الطلب وعرض رمز التتبع
     */
    public function success($slug, $code)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('tracking_code', $code)->firstOrFail();
        
        return view('themes.burger-theme.success', compact('restaurant', 'order'));
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
