<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Coupon;
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
        
        $themePath = ThemeHelper::getThemePath($restaurant);
        return view('themes.{$themePath}.orders.track', compact('restaurant', 'order'));
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
        $themePath = ThemeHelper::getThemePath($restaurant);

        return view('themes.{$themePath}.cart.checkout', compact('restaurant', 'total', 'slug'));
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
            'offer_id' => 'nullable|exists:offers,id',
            'coupon_id' => 'nullable|exists:coupons,id',
            'coupon_code' => 'nullable|string',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->back()->with('error', 'لا يمكن إتمام طلب فارغ');
        }

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

        // حساب خصم العرض (تلقائي)
        $offerDiscount = 0;
        $appliedOfferId = null;

        if ($request->offer_id) {
            $offer = Offer::where('id', $request->offer_id)
                ->where('restaurant_id', $restaurant->id)
                ->first();

            if ($offer && $offer->isCurrentlyActive() && $subtotal >= $offer->min_order_amount) {
                $offerDiscount = $offer->getDiscountAmount($subtotal);
                $appliedOfferId = $offer->id;
                $offer->incrementUsage();
            }
        }

        // حساب خصم الكوبون (يدوي)
        $couponDiscount = 0;
        $appliedCouponId = null;
        $appliedCouponCode = null;

        if ($request->coupon_id) {
            $coupon = Coupon::where('id', $request->coupon_id)
                ->where('restaurant_id', $restaurant->id)
                ->first();

            if ($coupon && $coupon->isCurrentlyActive()) {
                $validation = $coupon->isValidFor($subtotal, $request->phone);

                if ($validation['valid']) {
                    $couponDiscount = $coupon->getDiscountAmount($subtotal);
                    $appliedCouponId = $coupon->id;
                    $appliedCouponCode = $request->coupon_code;
                    $coupon->incrementUsage();
                }
            }
        }

        $tax = $subtotal * 0.15;
        $deliveryFee = $restaurant->delivery_fee ?? 0;
        $totalDiscount = $offerDiscount + $couponDiscount;
        $finalAmount = $subtotal + $tax + $deliveryFee - $totalDiscount;

        // إنشاء سجل الطلب
        $order = Order::create([
            'restaurant_id' => $restaurant->id,
            'customer_name' => $request->name,
            'customer_phone' => $request->phone,
            'delivery_address' => $request->address,
            'delivery_type' => 'delivery',
            'delivery_city' => $restaurant->city,
            'delivery_fee' => $deliveryFee,
            'subtotal' => $subtotal,
            'discount' => $offerDiscount, // خصم العرض
            'coupon_discount' => $couponDiscount, // خصم الكوبون
            'offer_id' => $appliedOfferId,
            'coupon_id' => $appliedCouponId,
            'coupon_code' => $appliedCouponCode,
            'total_amount' => $subtotal,
            'final_amount' => $finalAmount,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'notes' => $request->notes,
            'status' => 'pending',
            'tracking_code' => 'ORD-' . strtoupper(Str::random(6)),
        ]);

        // حفظ عناصر الطلب
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

        // إنشاء فاتورة
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

        session()->forget('cart');

        return redirect()->route('order.success', [$slug, $order->tracking_code]);
    }
    /**
     * التحقق من كوبون الخصم (AJAX)
     */
    public function applyCoupon(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $request->validate([
            'coupon_code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
            'customer_phone' => 'nullable|string',
        ]);

        $couponCode = strtoupper(trim($request->coupon_code));

        // البحث عن الكوبون
        $coupon = Coupon::where('restaurant_id', $restaurant->id)
            ->where('code', $couponCode)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'كود الخصم غير صحيح'
            ]);
        }

        // التحقق من الصلاحية
        $validation = $coupon->isValidFor($request->subtotal, $request->customer_phone);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message']
            ]);
        }

        // حساب مبلغ الخصم
        $discountAmount = $coupon->getDiscountAmount($request->subtotal);

        return response()->json([
            'success' => true,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'discount_label' => $coupon->getDiscountLabel(),
            'discount_amount' => $discountAmount,
            'message' => 'تم تطبيق الكوبون بنجاح!'
        ]);
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
        $themePath = ThemeHelper::getThemePath($restaurant);
        return view('themes.{$themePath}.orders.track-form', compact('restaurant'));
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
        $themePath = ThemeHelper::getThemePath($restaurant);

        return view('themes.{$themePath}.cart.success', compact('restaurant', 'order'));
    }
}
