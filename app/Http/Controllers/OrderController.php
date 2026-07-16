<?php

namespace App\Http\Controllers;

use App\Models\Offer;
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

        return view('themes.burger-theme.checkout', compact('restaurant', 'total', 'slug'));
    }

    /**
     * حفظ الطلب الجديد
     */
    public function store(Request $request, $slug)
{
    $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

    // ✅ تمت إضافة offer_id للتحقق من صحته
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'address' => 'required|string',
        'payment_method' => 'required|in:cash,transfer',
        'notes' => 'nullable|string',
        'offer_id' => 'nullable|exists:offers,id', // <-- إضافة جديدة
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

    // ✅ منطق حساب الخصم بناءً على العرض المختار
    $discount = 0;
    $appliedOfferId = null;

    if ($request->offer_id) {
        $offer = Offer::where('id', $request->offer_id)
            ->where('restaurant_id', $restaurant->id)
            ->first();

        // التحقق من أن العرض نشط ويحقق الحد الأدنى للطلب
        if ($offer && $offer->isCurrentlyActive() && $subtotal >= $offer->min_order_amount) {
            $discount = $offer->getDiscountAmount($subtotal);
            $appliedOfferId = $offer->id;
            
            // زيادة عدد مرات استخدام العرض
            $offer->incrementUsage();
        }
    }

    $tax = $subtotal * 0.15;
    $deliveryFee = $restaurant->delivery_fee ?? 0;
    
    // ✅ المعادلة النهائية تشمل الخصم المحسوب ديناميكياً
    $finalAmount = $subtotal + $tax + $deliveryFee - $discount;

    // إنشاء سجل الطلب الرئيسي
    $order = Order::create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => $request->name,
        'customer_phone' => $request->phone,
        'delivery_address' => $request->address,
        'delivery_type' => 'delivery', 
        'delivery_city' => $restaurant->city,
        'delivery_fee' => $deliveryFee,
        'subtotal' => $subtotal,
        'discount' => $discount, // ✅ تم حفظ قيمة الخصم
        'offer_id' => $appliedOfferId, // ✅ تم حفظ معرف العرض المطبق
        'total_amount' => $subtotal,
        'final_amount' => $finalAmount,
        'payment_method' => $request->payment_method,
        'payment_status' => 'pending',
        'notes' => $request->notes,
        'status' => 'pending',
        'tracking_code' => 'ORD-' . strtoupper(Str::random(6)),
    ]);

    // حفظ عناصر الطلب في جدول order_items (كودك الممتاز)
    foreach ($itemsData as $item) {
        $product = Product::find($item['product_id']);

        Order_item::create([
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'product_name' => $product->name,
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'total' => $item['subtotal'],
        ]);
    }

    // إنشاء فاتورة تلقائية (كودك الممتاز)
    $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);

    Invoice::create([
        'order_id' => $order->id,
        'restaurant_id' => $order->restaurant_id,
        'invoice_number' => $invoiceNumber,
        'subtotal' => $subtotal,
        'tax_amount' => $tax,
        'total_amount' => $finalAmount, // الفاتورة تعكس المبلغ النهائي بعد الخصم
        'status' => 'pending',
    ]);

    // تفريغ السلة بعد نجاح الطلب
    session()->forget('cart');

    return redirect()->route('order.success', [$slug, $order->tracking_code]);
}
    /**
     * التحقق من كود الخصم (AJAX)
     */
    public function applyOffer(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $request->validate([
            'offer_code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $offerCode = strtoupper(trim($request->offer_code));

        // البحث عن العرض
        $offer = Offer::where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            })
            ->first();

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'كود الخصم غير صحيح أو منتهي الصلاحية'
            ]);
        }

        // التحقق من الحد الأدنى للطلب
        if ($request->subtotal < $offer->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'الحد الأدنى للطلب لتطبيق هذا العرض هو ' . number_format($offer->min_order_amount, 2) . ' ر.س'
            ]);
        }

        // حساب مبلغ الخصم
        $discountAmount = $offer->getDiscountAmount($request->subtotal);

        return response()->json([
            'success' => true,
            'offer_id' => $offer->id,
            'offer_title' => $offer->title,
            'discount_label' => $offer->getDiscountLabel(),
            'discount_amount' => $discountAmount,
            'message' => 'تم تطبيق العرض بنجاح!'
        ]);
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
