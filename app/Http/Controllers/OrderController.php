<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Helpers\ThemeHelper;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Rules\CustomerPhoneNumber;
use App\Services\CartService;
use App\Services\DeliveryPricingService;
use App\Services\ReservationAvailabilityService;
use App\Services\RestaurantTableSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly DeliveryPricingService $deliveryPricing,
        private readonly RestaurantTableSession $tableSession,
        private readonly CartService $cartService,
        private readonly ReservationAvailabilityService $reservationAvailability,
    ) {}

    public function downloadPaymentReceipt(Request $request, Order $order): BinaryFileResponse
    {
        abort_unless($request->user()?->role === 'restaurant_admin', 403);
        Gate::authorize('view', $order);
        abort_if(blank($order->payment_receipt), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($order->payment_receipt), 404);

        return response()->download($disk->path($order->payment_receipt));
    }

    public function track(Request $request, string $slug, string $code)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $code)
            ->with(['invoice', 'items.product', 'driver', 'deliveryDispatch.provider'])
            ->firstOrFail();

        $this->authorizeOrderTracking($request, $order);

        $requestToken = $request->query('token');
        if (filled($order->device_token) && is_string($requestToken) && hash_equals($order->device_token, $requestToken)) {
            $request->session()->put('order_device_tokens.'.$order->tracking_code, $order->device_token);
            Cookie::queue('order_device_token_'.$order->id, $order->device_token, 60 * 24 * 7);
        }

        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.orders.track", compact('restaurant', 'order'));
    }

    public function trackingLocation(Request $request, string $slug, string $code): JsonResponse
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $code)
            ->with(['invoice', 'restaurantTable', 'deliveryDispatch.provider'])
            ->firstOrFail();

        $this->authorizeOrderTracking($request, $order);

        return response()->json([
            'status' => $order->status,
            'delivery_dispatch' => $order->deliveryDispatch ? [
                'status' => $order->deliveryDispatch->status,
                'provider_name' => $order->deliveryDispatch->provider?->name,
            ] : null,
            'rejection_reason' => $order->rejection_reason,
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

    private function authorizeOrderTracking(Request $request, Order $order): void
    {
        $requestToken = $request->query('token');
        $cookieToken = $request->cookie('order_device_token_'.$order->id);
        $sessionToken = $request->session()->get('order_device_tokens.'.$order->tracking_code);
        $hasTrackingToken = filled($order->device_token)
            && is_string($requestToken)
            && hash_equals($order->device_token, $requestToken);
        $hasDeviceCookie = filled($order->device_token)
            && is_string($cookieToken)
            && hash_equals($order->device_token, $cookieToken);
        $hasSessionToken = filled($order->device_token)
            && is_string($sessionToken)
            && hash_equals($order->device_token, $sessionToken);
        $user = $request->user();
        $isAssignedDriver = $user?->canAccessDriverPanelFor($order->restaurant)
            && $order->driver_id === $user->id;
        $isRestaurantAdmin = $user?->role === 'super_admin'
            || ($user?->isRestaurantAdmin() && (int) $user->restaurant_id === (int) $order->restaurant_id);

        abort_unless(
            $hasTrackingToken || $hasDeviceCookie || $hasSessionToken || $isAssignedDriver || $isRestaurantAdmin,
            403,
            'لا تملك صلاحية تتبع هذا الطلب.'
        );

    }

    public function checkout($slug)
    {
        $restaurant = Restaurant::with('theme')->where('slug', $slug)->firstOrFail();

        if (! $restaurant->canAcceptOrders()) {
            return redirect()->route('restaurant.menu', $slug)
                ->with('error', $restaurant->orderUnavailableMessage());
        }

        $cart = $this->cartService->get($restaurant);

        if (empty($cart)) {
            return redirect()->route('restaurant.menu', $slug)->with('error', 'السلة فارغة');
        }

        $productIds = collect($cart)->pluck('product_id')->unique();
        $products = Product::where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            $this->cartService->forget($restaurant);

            return redirect()->route('restaurant.menu', $slug)
                ->with('error', 'تحتوي السلة على منتج غير متاح لهذا المطعم');
        }

        $subtotal = 0;
        $checkoutItems = [];
        foreach ($cart as $details) {
            $quantity = (int) $details['quantity'];

            if ($quantity < 1) {
                return redirect()->route('restaurant.menu', $slug)
                    ->with('error', 'تحتوي السلة على كمية غير صالحة');
            }

            $product = $products->get($details['product_id']);
            $unitPrice = (float) ($details['unit_price'] ?? $product->price);
            $itemSubtotal = $unitPrice * $quantity;
            $subtotal += $itemSubtotal;
            $checkoutItems[] = [
                'name' => $product->name,
                'variant_name' => $details['variant_name'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $itemSubtotal,
            ];
        }

        $taxRate = (float) ($restaurant->tax_percentage ?? 0);
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = $subtotal + $tax;

        // ===== الحسابات الخاصة بصفحة إتمام الطلب (Checkout Calculations) =====
        $baseSubtotal = $subtotal;
        $baseTax = $tax;
        $deliveryEnabled = $restaurant->deliveryFulfillmentAvailable();
        $deliveryFee = $deliveryEnabled ? ($restaurant->delivery_fee ?? 0) : 0;
        $restaurantLatitude = filled($restaurant->latitude) ? (float) $restaurant->latitude : 33.5138;
        $restaurantLongitude = filled($restaurant->longitude) ? (float) $restaurant->longitude : 36.2765;

        // حساب خصم العرض التلقائي
        /** @var Offer|null $activeOffer */
        $activeOffer = $restaurant->activeOffers()
            ->where('min_order_amount', '<=', $baseSubtotal)
            ->first();

        $autoDiscount = $activeOffer ? $activeOffer->getDiscountAmount($baseSubtotal) : 0;

        // الإجمالي النهائي الأولي
        $initialFinalTotal = $baseSubtotal + $baseTax + $deliveryFee - $autoDiscount;

        $themePath = ThemeHelper::getThemePath($restaurant);

        $table = $this->currentTable($restaurant);
        $isAlbashaBurger = $restaurant->theme?->folder_name === 'albasha-burger';
        $experimentalOrderFeatures = $restaurant->getExperimentalOrderFeatures();
        $supportsPrepaidOrders = $restaurant->hasFeature('bank_transfer') && filled($restaurant->bank_details);
        $supportsTableReservations = $isAlbashaBurger
            && $supportsPrepaidOrders
            && $restaurant->serviceEnabled('dine_in')
            && $restaurant->reservationsEnabled();
        $supportsScheduledPickup = $isAlbashaBurger
            && $supportsPrepaidOrders
            && $restaurant->serviceEnabled('takeaway')
            && $restaurant->scheduledPickupEnabled();
        $depositPercentage = (float) $experimentalOrderFeatures['deposit_percentage'];
        $availableOrderTypes = array_filter([
            'delivery' => $deliveryEnabled,
            'dine_in' => $supportsTableReservations,
            'takeaway' => $supportsScheduledPickup,
        ]);
        $initialDeliveryType = $table
            ? 'dine_in'
            : (string) old('delivery_type', array_key_first($availableOrderTypes) ?: '');
        if (! $table && ! array_key_exists($initialDeliveryType, $availableOrderTypes)) {
            $initialDeliveryType = array_key_first($availableOrderTypes) ?: '';
        }

        // ===== استرجاع بيانات العميل المحفوظة سابقاً من الكوكيز =====
        $savedCustomer = [
            'name' => request()->cookie('customer_name'),
            'phone' => request()->cookie('customer_phone'),
            'address' => request()->cookie('customer_address'),
            'lat' => request()->cookie('customer_lat'),
            'lng' => request()->cookie('customer_lng'),
        ];
        $checkoutToken = (string) Str::uuid();
        request()->session()->put('checkout_fallback_tokens.'.$restaurant->id, $checkoutToken);

        return view("themes.{$themePath}.cart.checkout", compact(
            'restaurant',
            'restaurantLatitude',
            'restaurantLongitude',
            'total',
            'slug',
            'table',
            'supportsTableReservations',
            'supportsScheduledPickup',
            'deliveryEnabled',
            'experimentalOrderFeatures',
            'depositPercentage',
            'initialDeliveryType',
            'baseSubtotal',
            'baseTax',
            'checkoutItems',
            'deliveryFee',
            'activeOffer',
            'autoDiscount',
            'initialFinalTotal',
            'savedCustomer',
            'checkoutToken',
        ));
    }

    public function tableAvailability(Request $request, string $slug): JsonResponse
    {
        $restaurant = Restaurant::with('theme')->where('slug', $slug)->firstOrFail();

        abort_unless(
            $restaurant->hasFeature('bank_transfer')
                && filled($restaurant->bank_details)
                && $restaurant->serviceEnabled('dine_in')
                && $restaurant->serviceEnabled('table_reservations'),
            404,
        );

        $validated = $request->validate([
            'arrival_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:now'],
            'guest_count' => ['required', 'integer', 'between:1,20'],
            'duration_minutes' => ['required', Rule::in(
                $restaurant->getExperimentalOrderFeatures()['reservation_duration_options'] ?: [90],
            )],
        ]);

        $arrivalAt = Carbon::parse($validated['arrival_at']);

        $tables = $this->reservationAvailability->availableTables(
            $restaurant,
            $arrivalAt,
            (int) $validated['guest_count'],
            (int) $validated['duration_minutes'],
        )
            ->map(fn (RestaurantTable $table): array => [
                'id' => $table->id,
                'number' => $table->number,
                'name' => $table->name,
                'seats' => $table->seats,
            ]);

        return response()->json(['tables' => $tables]);
    }

    /**
     * حفظ الطلب الجديد
     */
    public function store(Request $request, $slug)
    {
        $restaurant = Restaurant::with('theme')->where('slug', $slug)->firstOrFail();
        $checkoutToken = $request->validate([
            'checkout_token' => ['nullable', 'uuid'],
        ])['checkout_token'] ?? $request->session()->get('checkout_fallback_tokens.'.$restaurant->id);

        if (! is_string($checkoutToken) || ! Str::isUuid($checkoutToken)) {
            $checkoutToken = (string) Str::uuid();
            $request->session()->put('checkout_fallback_tokens.'.$restaurant->id, $checkoutToken);
        }

        $existingOrder = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('checkout_token', $checkoutToken)
            ->first();

        if ($existingOrder) {
            return $this->redirectToCheckoutOrder($request, $restaurant, $existingOrder);
        }

        if (! $restaurant->canAcceptOrders()) {
            throw ValidationException::withMessages([
                'availability' => $restaurant->orderUnavailableMessage(),
            ]);
        }

        $table = $this->currentTable($restaurant);
        $experimentalOrderFeatures = $restaurant->getExperimentalOrderFeatures();
        $supportsPrepaidOrders = $restaurant->hasFeature('bank_transfer') && filled($restaurant->bank_details);
        $supportsTableReservations = $restaurant->serviceEnabled('dine_in')
            && $restaurant->reservationsEnabled()
            && $supportsPrepaidOrders;
        $supportsScheduledPickup = $restaurant->serviceEnabled('takeaway')
            && $restaurant->scheduledPickupEnabled()
            && $supportsPrepaidOrders;
        $reservationDurationOptions = $experimentalOrderFeatures['reservation_duration_options'] ?: [90];
        $allowedDeliveryTypes = [];
        if ($restaurant->deliveryFulfillmentAvailable()) {
            $allowedDeliveryTypes[] = 'delivery';
        }
        if (($table || $supportsTableReservations) && $restaurant->serviceEnabled('dine_in')) {
            $allowedDeliveryTypes[] = 'dine_in';
        }
        if ($supportsScheduledPickup) {
            $allowedDeliveryTypes[] = 'takeaway';
        }

        if ($allowedDeliveryTypes === []) {
            throw ValidationException::withMessages([
                'delivery_type' => 'لا توجد خدمة طلب متاحة حالياً لهذا المطعم.',
            ]);
        }

        $defaultDeliveryType = $allowedDeliveryTypes[0];
        $request->merge([
            'delivery_type' => $table ? 'dine_in' : ($request->input('delivery_type') ?: $defaultDeliveryType),
        ]);

        $deliveryType = $table ? 'dine_in' : $request->input('delivery_type', 'delivery');
        $isDineInReservation = ! $table && $deliveryType === 'dine_in' && $supportsTableReservations;
        $isScheduledPickup = ! $table && $deliveryType === 'takeaway' && $supportsScheduledPickup;
        $isDineIn = (bool) $table || $isDineInReservation;
        $requiresDeliveryLocation = ! $isDineIn && ! $isScheduledPickup;

        $requiredDeposit = $isDineInReservation || $isScheduledPickup;
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => trim($request->input('phone'))]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', new CustomerPhoneNumber],
            'address' => 'nullable|string|max:1000',
            'latitude' => [$requiresDeliveryLocation ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [$requiresDeliveryLocation ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
            'payment_method' => $requiredDeposit ? ['required', Rule::in(['bank_transfer'])] : 'required|in:cash,transfer,bank_transfer',
            'notes' => 'nullable|string',
            'offer_id' => 'nullable|exists:offers,id',
            'coupon_id' => 'nullable|exists:coupons,id',
            'coupon_code' => 'nullable|string',
            'delivery_type' => ['required', Rule::in($allowedDeliveryTypes)],
            'restaurant_table_id' => $isDineInReservation
                ? [
                    'required',
                    Rule::exists('restaurant_tables', 'id')
                        ->where('restaurant_id', $restaurant->id)
                        ->where('is_active', true),
                ]
                : 'prohibited',
            'dine_in_arrival_at' => [
                $isDineInReservation ? 'required' : 'prohibited',
                'date_format:Y-m-d\TH:i',
                'after:now',
            ],
            'dine_in_duration_minutes' => [
                $isDineInReservation ? 'required' : 'prohibited',
                Rule::in($reservationDurationOptions),
            ],
            'dine_in_guest_count' => [
                $isDineInReservation ? 'required' : 'prohibited',
                'integer',
                'between:1,20',
            ],
            'dine_in_seating_preference' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_seating_preference'] ? 'nullable' : 'prohibited',
                'in:any,indoor,outdoor,window,quiet,family',
            ],
            'dine_in_occasion' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_occasion'] ? 'nullable' : 'prohibited',
                'in:none,birthday,anniversary,family,business,other',
            ],
            'dine_in_high_chair_count' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_high_chairs'] ? 'nullable' : 'prohibited',
                'integer',
                'between:0,10',
                'lte:dine_in_guest_count',
            ],
            'dine_in_stroller_space' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_stroller_space'] ? 'nullable' : 'prohibited',
                'boolean',
            ],
            'dine_in_accessibility_notes' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_accessibility_notes'] ? 'nullable' : 'prohibited',
                'string',
                'max:1000',
            ],
            'dine_in_allergy_notes' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_allergy_notes'] ? 'nullable' : 'prohibited',
                'string',
                'max:1000',
            ],
            'dine_in_dietary_preference' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_dietary_preference'] ? 'nullable' : 'prohibited',
                'in:none,vegetarian,vegan,gluten_free,other',
            ],
            'dine_in_confirmation_call' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_confirmation_call'] ? 'nullable' : 'prohibited',
                'boolean',
            ],
            'dine_in_celebration_notes' => [
                $isDineInReservation && $experimentalOrderFeatures['reservation_celebration_notes'] ? 'nullable' : 'prohibited',
                'string',
                'max:1000',
            ],
            'requested_pickup_at' => [
                $isScheduledPickup ? 'required' : 'prohibited',
                'date_format:Y-m-d\TH:i',
                'after:now',
            ],
            'payment_receipt' => [
                $requiredDeposit ? 'required' : 'prohibited',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.string' => 'أدخل رقم هاتف صالحاً بصيغة محلية أو دولية.',
            'phone.max' => 'يجب ألا يتجاوز رقم الهاتف 20 حرفاً.',
        ]);

        if ($requiredDeposit && (! $restaurant->hasFeature('bank_transfer') || blank($restaurant->bank_details))) {
            throw ValidationException::withMessages([
                'payment_method' => 'تعذر تفعيل الدفع المسبق لعدم توفر تفاصيل التحويل لدى المطعم.',
            ]);
        }

        $customerLatitude = $validated['latitude'] ?? null;
        $customerLongitude = $validated['longitude'] ?? null;
        $distanceKm = null;
        $deliveryFee = 0.0;

        if ($requiresDeliveryLocation) {
            $restaurantLatitude = filled($restaurant->latitude) ? (float) $restaurant->latitude : 33.5138;
            $restaurantLongitude = filled($restaurant->longitude) ? (float) $restaurant->longitude : 36.2765;
            $distance = $this->deliveryPricing->distanceInKilometers(
                $restaurantLatitude,
                $restaurantLongitude,
                (float) $customerLatitude,
                (float) $customerLongitude,
            );

            if ($distance > (float) $restaurant->delivery_radius_km) {
                $distanceLabel = number_format($distance, 2);
                $radiusLabel = number_format((float) $restaurant->delivery_radius_km, 2);

                throw ValidationException::withMessages([
                    'latitude' => "نعتذر، موقعك يبعد {$distanceLabel} كم وهو خارج نطاق التوصيل المتاح لهذا المطعم (الحد الأقصى {$radiusLabel} كم)",
                ]);
            }

            $distanceKm = round($distance, 2);
            $deliveryFee = $this->deliveryPricing->feeForDistance(
                $distance,
                (float) $restaurant->base_delivery_fee,
                (float) $restaurant->base_delivery_km,
                (float) $restaurant->per_km_delivery_fee,
            );
        }

        $cart = $this->cartService->get($restaurant);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'لا يمكن إتمام طلب فارغ');
        }

        $subtotal = 0;
        $itemsData = [];
        $productIds = collect($cart)->pluck('product_id')->unique();
        $products = Product::where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            return redirect()->back()->with('error', 'تحتوي السلة على منتج غير تابع لهذا المطعم');
        }

        foreach ($cart as $details) {
            $quantity = (int) $details['quantity'];

            if ($quantity < 1) {
                return redirect()->back()->with('error', 'تحتوي السلة على كمية غير صالحة');
            }

            $product = $products->get($details['product_id']);
            $unitPrice = (float) ($details['unit_price'] ?? $product->price);
            $itemSubtotal = $unitPrice * $quantity;
            $subtotal += $itemSubtotal;
            $itemsData[] = [
                'product_id' => $details['product_id'],
                'quantity' => $quantity,
                'price' => $unitPrice,
                'variant_name' => $details['variant_name'] ?? null,
                'subtotal' => $itemSubtotal,
            ];
        }

        // حساب خصم العرض (تلقائي)
        $offerDiscount = 0;
        $appliedOfferId = null;
        $offer = null;

        if ($request->offer_id) {
            $offer = Offer::where('id', $request->offer_id)
                ->where('restaurant_id', $restaurant->id)
                ->first();

            if ($offer && $offer->isCurrentlyActive() && $subtotal >= $offer->min_order_amount) {
                $offerDiscount = $offer->getDiscountAmount($subtotal);
                $appliedOfferId = $offer->id;
            }
        }

        // حساب خصم الكوبون (يدوي)
        $couponDiscount = 0;
        $appliedCouponId = null;
        $appliedCouponCode = null;
        $coupon = null;

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
                }
            }
        }

        $subtotal = round($subtotal, 2);
        $offerDiscount = round($offerDiscount, 2);
        $couponDiscount = round($couponDiscount, 2);
        $taxRate = (float) ($restaurant->tax_percentage ?? 0);
        $tax = round($subtotal * $taxRate / 100, 2);
        $finalAmount = round($subtotal + $tax + $deliveryFee - ($offerDiscount + $couponDiscount), 2);
        $restaurant->refresh();
        $depositAmount = $requiredDeposit
            ? round($finalAmount * (float) $experimentalOrderFeatures['deposit_percentage'] / 100, 2)
            : 0;
        if (! $restaurant->canAcceptOrders()) {
            throw ValidationException::withMessages([
                'availability' => $restaurant->orderUnavailableMessage(),
            ]);
        }

        $receiptPath = $requiredDeposit
            ? $validated['payment_receipt']->store('payment-receipts', 'local')
            : null;
        $deviceToken = Str::random(40);
        try {
            $order = DB::transaction(function () use (
                $restaurant,
                $table,
                $isDineInReservation,
                $isScheduledPickup,
                $validated,
                $deliveryType,
                $customerLatitude,
                $customerLongitude,
                $distanceKm,
                $deliveryFee,
                $subtotal,
                $tax,
                $offerDiscount,
                $couponDiscount,
                $appliedOfferId,
                $appliedCouponId,
                $appliedCouponCode,
                $finalAmount,
                $request,
                $deviceToken,
                $depositAmount,
                $receiptPath,
                $offer,
                $coupon,
                $checkoutToken,
                $products,
                $itemsData,
            ): Order {
                $lockedRestaurant = Restaurant::query()
                    ->whereKey($restaurant->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $existingOrder = Order::query()
                    ->where('restaurant_id', $lockedRestaurant->id)
                    ->where('checkout_token', $checkoutToken)
                    ->lockForUpdate()
                    ->first();

                if ($existingOrder) {
                    return $existingOrder;
                }

                $reservationTable = null;

                if ($isDineInReservation) {
                    $reservationTable = $this->reservationAvailability->availableTables(
                        $lockedRestaurant,
                        Carbon::parse($validated['dine_in_arrival_at']),
                        (int) $validated['dine_in_guest_count'],
                        (int) $validated['dine_in_duration_minutes'],
                    )->firstWhere('id', (int) $validated['restaurant_table_id']);

                    if (! $reservationTable) {
                        throw ValidationException::withMessages([
                            'restaurant_table_id' => 'لم تعد هذه الطاولة متاحة في الموعد المحدد. اختر طاولة أخرى أو غيّر وقت الوصول.',
                        ]);
                    }
                }

                if ($appliedOfferId && $offer) {
                    $offer->incrementUsage();
                }

                if ($appliedCouponId && $coupon) {
                    $coupon->incrementUsage();
                }

                $order = Order::create([
                    'restaurant_id' => $restaurant->id,
                    'restaurant_table_id' => $table?->id ?? $reservationTable?->id,
                    'customer_name' => $request->name,
                    'customer_phone' => $validated['phone'],
                    'delivery_address' => $table
                        ? 'طاولة '.$table->number
                        : ($isDineInReservation
                            ? 'حجز طاولة '.$reservationTable->number
                            : ($isScheduledPickup ? 'استلام من المطعم' : ($validated['address'] ?? 'موقع محدد على الخريطة'))),
                    'delivery_latitude' => $validated['latitude'] ?? null,
                    'delivery_longitude' => $validated['longitude'] ?? null,
                    'customer_lat' => $customerLatitude,
                    'customer_lng' => $customerLongitude,
                    'distance_km' => $distanceKm,
                    'delivery_type' => $deliveryType,
                    'dine_in_arrival_at' => $isDineInReservation ? $validated['dine_in_arrival_at'] : null,
                    'dine_in_duration_minutes' => $isDineInReservation ? $validated['dine_in_duration_minutes'] : null,
                    'reservation_status' => $isDineInReservation ? 'pending' : null,
                    'requested_pickup_at' => $isScheduledPickup ? $validated['requested_pickup_at'] : null,
                    'deposit_amount' => $depositAmount,
                    'dine_in_guest_count' => $isDineInReservation ? $validated['dine_in_guest_count'] : null,
                    'dine_in_seating_preference' => $isDineInReservation ? ($validated['dine_in_seating_preference'] ?? null) : null,
                    'dine_in_occasion' => $isDineInReservation ? ($validated['dine_in_occasion'] ?? null) : null,
                    'dine_in_high_chair_count' => $isDineInReservation ? ($validated['dine_in_high_chair_count'] ?? null) : null,
                    'dine_in_stroller_space' => $isDineInReservation && ($validated['dine_in_stroller_space'] ?? false),
                    'dine_in_accessibility_notes' => $isDineInReservation ? ($validated['dine_in_accessibility_notes'] ?? null) : null,
                    'dine_in_allergy_notes' => $isDineInReservation ? ($validated['dine_in_allergy_notes'] ?? null) : null,
                    'dine_in_dietary_preference' => $isDineInReservation ? ($validated['dine_in_dietary_preference'] ?? null) : null,
                    'dine_in_confirmation_call' => $isDineInReservation && ($validated['dine_in_confirmation_call'] ?? false),
                    'dine_in_celebration_notes' => $isDineInReservation ? ($validated['dine_in_celebration_notes'] ?? null) : null,
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
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending',
                    'payment_receipt' => $receiptPath,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'pending',
                    'device_token' => $deviceToken,
                    'tracking_code' => 'ORD-'.strtoupper(Str::random(6)),
                    'checkout_token' => $checkoutToken,
                ]);

                foreach ($itemsData as $item) {
                    $product = $products->get($item['product_id']);

                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'variant_name' => $item['variant_name'],
                        'total' => $item['subtotal'],
                    ]);
                }

                $invoiceNumber = 'INV-'.date('Y').'-'.str_pad($order->id, 6, '0', STR_PAD_LEFT);

                $order->invoice()->create([
                    'restaurant_id' => $order->restaurant_id,
                    'invoice_number' => $invoiceNumber,
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $finalAmount,
                    'final_amount' => $finalAmount,
                    'status' => 'pending',
                ]);

                return $order;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $order = Order::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('checkout_token', $checkoutToken)
                ->first();

            if (! $order) {
                if ($receiptPath) {
                    Storage::disk('local')->delete($receiptPath);
                }

                throw $exception;
            }
        } catch (\Throwable $exception) {
            if ($receiptPath) {
                Storage::disk('local')->delete($receiptPath);
            }

            throw $exception;
        }

        if (! $order->wasRecentlyCreated && $receiptPath && $receiptPath !== $order->payment_receipt) {
            Storage::disk('local')->delete($receiptPath);
        }

        // ===== حفظ بيانات الزبون في الكوكيز للزيارات القادمة (لمدة 30 يوماً) =====
        Cookie::queue('customer_name', $request->name, 60 * 24 * 30);
        Cookie::queue('customer_phone', $request->phone, 60 * 24 * 30);
        Cookie::queue('customer_address', $request->address, 60 * 24 * 30);
        if ($customerLatitude && $customerLongitude) {
            Cookie::queue('customer_lat', $customerLatitude, 60 * 24 * 30);
            Cookie::queue('customer_lng', $customerLongitude, 60 * 24 * 30);
        }

        if ($order->wasRecentlyCreated && ! $requiredDeposit) {
            event(new OrderCreated($order));
        }

        return $this->redirectToCheckoutOrder($request, $restaurant, $order, $order->wasRecentlyCreated);
    }

    private function redirectToCheckoutOrder(
        Request $request,
        Restaurant $restaurant,
        Order $order,
        bool $clearCheckoutState = false,
    ): RedirectResponse {
        Cookie::queue('order_device_token_'.$order->id, $order->device_token, 60 * 24 * 7);
        $request->session()->put('order_device_tokens.'.$order->tracking_code, $order->device_token);

        if ($clearCheckoutState) {
            $this->cartService->forget($restaurant);
            $this->tableSession->forget();
        }

        return redirect()->route('order.success', [$restaurant->slug, $order->tracking_code]);
    }

    protected function currentTable(Restaurant $restaurant): ?RestaurantTable
    {
        return $this->tableSession->resolve($restaurant);
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
                'message' => 'الحد الأدنى للطلب لتطبيق هذا العرض هو '.number_format($offer->min_order_amount, 2).' ل.س',
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
    public function success(Request $request, string $slug, string $code)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $code)
            ->firstOrFail();

        $this->authorizeOrderTracking($request, $order);

        $themePath = ThemeHelper::getThemePath($restaurant);

        return view("themes.{$themePath}.cart.success", compact('restaurant', 'order'));
    }
}
