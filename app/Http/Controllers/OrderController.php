<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Helpers\ThemeHelper;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function track($slug, $code)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $code)
            ->with('invoice')
            ->firstOrFail();

        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.orders.track", compact('restaurant', 'order'));
    }

    public function trackingLocation($slug, $code): JsonResponse
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $code)
            ->with(['invoice', 'restaurantTable'])
            ->firstOrFail();

        return response()->json([
            'status' => $order->status,
            'driver_arrived_at' => $order->driver_arrived_at?->toIso8601String(),
            'customer' => [
                'latitude' => $order->delivery_latitude,
                'longitude' => $order->delivery_longitude,
            ],
            'driver' => [
                'latitude' => $order->driver_latitude,
                'longitude' => $order->driver_longitude,
                'updated_at' => $order->driver_location_updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function checkout($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $sessionKey = 'cart_'.$restaurant->id;
        $cart = session($sessionKey, []);

        if (empty($cart)) {
            return redirect()->route('restaurant.menu', $slug)->with('error', 'السلة فارغة');
        }

        $productIds = array_keys($cart);
        $products = Product::where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count(array_unique($productIds))) {
            session()->forget($sessionKey);

            return redirect()->route('restaurant.menu', $slug)
                ->with('error', 'تحتوي السلة على منتج غير متاح لهذا المطعم');
        }

        $subtotal = 0;
        foreach ($cart as $id => $details) {
            $quantity = (int) ($details['qty'] ?? 0);

            if ($quantity < 1) {
                return redirect()->route('restaurant.menu', $slug)
                    ->with('error', 'تحتوي السلة على كمية غير صالحة');
            }

            $subtotal += $products->get($id)->price * $quantity;
        }

        $taxRate = (float) ($restaurant->tax_percentage ?? 0);
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;

        // ===== الحسابات الخاصة بصفحة إتمام الطلب (Checkout Calculations) =====
        $baseSubtotal = $subtotal;
        $baseTax = $tax;
        $deliveryFee = $restaurant->delivery_fee ?? 0;

        // حساب خصم العرض التلقائي
        /** @var Offer|null $activeOffer */
        $activeOffer = $restaurant->activeOffers()
            ->where('min_order_amount', '<=', $baseSubtotal)
            ->first();

        $autoDiscount = $activeOffer ? $activeOffer->getDiscountAmount($baseSubtotal) : 0;

        // الإجمالي النهائي الأولي
        $initialFinalTotal = $baseSubtotal + $baseTax + $deliveryFee - $autoDiscount;

        $themePath = ThemeHelper::getThemePath($restaurant);

        $table = session('restaurant_table_restaurant_id') === $restaurant->id
            ? $restaurant->tables()->find(session('restaurant_table_id'))
            : null;

        return view("themes.{$themePath}.cart.checkout", compact(
            'restaurant',
            'total',
            'slug',
            'table',
            'baseSubtotal',
            'baseTax',
            'deliveryFee',
            'activeOffer',
            'autoDiscount',
            'initialFinalTotal'
        ));
    }

    /**
     * حفظ الطلب الجديد
     */
    public function store(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $table = $this->currentTable($restaurant);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(09|\+?9639)\d{8}$/'],
            'address' => 'nullable|string|max:1000',
            'latitude' => [$table ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitude' => [$table ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'payment_method' => 'required|in:cash,transfer,bank_transfer',
            'notes' => 'nullable|string',
            'offer_id' => 'nullable|exists:offers,id',
            'coupon_id' => 'nullable|exists:coupons,id',
            'coupon_code' => 'nullable|string',
        ]);

        $sessionKey = 'cart_'.$restaurant->id;
        $cart = session($sessionKey, []);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'لا يمكن إتمام طلب فارغ');
        }

        $subtotal = 0;
        $itemsData = [];
        $productIds = array_keys($cart);
        $products = Product::where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count(array_unique($productIds))) {
            return redirect()->back()->with('error', 'تحتوي السلة على منتج غير تابع لهذا المطعم');
        }

        foreach ($cart as $id => $details) {
            $quantity = (int) ($details['qty'] ?? 0);

            if ($quantity < 1) {
                return redirect()->back()->with('error', 'تحتوي السلة على كمية غير صالحة');
            }

            $product = $products->get($id);
            $subtotal += $product->price * $quantity;
            $itemsData[] = [
                'product_id' => $id,
                'quantity' => $quantity,
                'price' => $product->price,
                'subtotal' => $product->price * $quantity,
            ];
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

        $subtotal = round($subtotal, 2);
        $offerDiscount = round($offerDiscount, 2);
        $couponDiscount = round($couponDiscount, 2);
        $taxRate = (float) ($restaurant->tax_percentage ?? 0);
        $tax = round($subtotal * $taxRate / 100, 2);
        $deliveryFee = $table ? 0 : round((float) ($restaurant->delivery_fee ?? 0), 2);
        $finalAmount = round($subtotal + $tax + $deliveryFee - ($offerDiscount + $couponDiscount), 2);
        $deviceToken = Str::random(40);
        // إنشاء سجل الطلب
        $order = Order::create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table?->id,
            'customer_name' => $request->name,
            'customer_phone' => $request->phone,
            'delivery_address' => $table ? 'طاولة '.$table->number : ($request->address ?: 'موقع محدد على الخريطة'),
            'delivery_latitude' => $request->latitude,
            'delivery_longitude' => $request->longitude,
            'delivery_type' => $table ? 'dine_in' : 'delivery',
            'delivery_city' => $restaurant->city,
            'delivery_fee' => $deliveryFee,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount' => $offerDiscount,
            'discount_amount' => $offerDiscount,
            'coupon_discount' => $couponDiscount,
            'offer_id' => $appliedOfferId,
            'coupon_id' => $appliedCouponId,
            'coupon_code' => $appliedCouponCode,
            'total_amount' => $finalAmount,
            'final_amount' => $finalAmount,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'notes' => $request->notes,
            'status' => 'pending',
            'device_token' => $deviceToken,
            'tracking_code' => 'ORD-'.strtoupper(Str::random(6)),
        ]);
        Cookie::queue('order_device_token_'.$order->id, $deviceToken, 60 * 24 * 7);
        $request->session()->put('order_device_tokens.'.$order->tracking_code, $deviceToken);
        // حفظ عناصر الطلب
        foreach ($itemsData as $item) {
            $product = $products->get($item['product_id']);

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
        $invoiceNumber = 'INV-'.date('Y').'-'.str_pad($order->id, 6, '0', STR_PAD_LEFT);

        Invoice::create([
            'order_id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'invoice_number' => $invoiceNumber,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $finalAmount,
            'final_amount' => $finalAmount,
            'status' => 'pending',
        ]);

        // ✅ التعديل الثالث: حذف سلة هذا المطعم فقط بعد نجاح الطلب
        session()->forget($sessionKey);
        session()->forget(['restaurant_table_id', 'restaurant_table_restaurant_id']);

        event(new OrderCreated($order));

        return redirect()->route('order.success', [$slug, $order->tracking_code]);
    }

    protected function currentTable(Restaurant $restaurant): ?RestaurantTable
    {
        if (session('restaurant_table_restaurant_id') !== $restaurant->id) {
            return null;
        }

        return $restaurant->tables()->whereKey(session('restaurant_table_id'))->where('is_active', true)->first();
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

        $coupon = Coupon::where('restaurant_id', $restaurant->id)
            ->where('code', $couponCode)
            ->first();

        if (! $coupon) {
            return response()->json([
                'success' => false,
                'message' => 'كود الخصم غير صحيح',
            ]);
        }

        $validation = $coupon->isValidFor($request->subtotal, $request->customer_phone);

        if (! $validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ]);
        }

        $discountAmount = $coupon->getDiscountAmount($request->subtotal);

        return response()->json([
            'success' => true,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'discount_label' => $coupon->getDiscountLabel(),
            'discount_amount' => $discountAmount,
            'message' => 'تم تطبيق الكوبون بنجاح!',
        ]);
    }

    /**
     * التحقق من كود الخصم (AJAX)
     */
    public function applyOffer(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $request->validate([
            'offer_id' => 'required|integer|exists:offers,id',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $offerQuery = Offer::where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            });

        $offerQuery->whereKey($request->integer('offer_id'));

        $offer = $offerQuery->first();

        if (! $offer) {
            return response()->json([
                'success' => false,
                'message' => 'كود الخصم غير صحيح أو منتهي الصلاحية',
            ]);
        }

        if ($request->subtotal < $offer->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'الحد الأدنى للطلب لتطبيق هذا العرض هو '.number_format($offer->min_order_amount, 2).' ر.س',
            ]);
        }

        $discountAmount = $offer->getDiscountAmount($request->subtotal);

        return response()->json([
            'success' => true,
            'offer_id' => $offer->id,
            'offer_title' => $offer->title,
            'discount_label' => $offer->getDiscountLabel(),
            'discount_amount' => $discountAmount,
            'message' => 'تم تطبيق العرض بنجاح!',
        ]);
    }

    /**
     * صفحة إدخال رمز التتبع
     */
    public function trackForm($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.orders.track-form", compact('restaurant'));
    }

    /**
     * معالجة نموذج إدخال رمز التتبع
     */
    public function trackSearch(Request $request, $slug)
    {
        $request->validate([
            'tracking_code' => 'required|string',
        ]);

        $code = strtoupper(trim($request->tracking_code));

        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $code)
            ->first();

        if (! $order) {
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
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $code)
            ->firstOrFail();
        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.cart.success", compact('restaurant', 'order'));
    }
}
