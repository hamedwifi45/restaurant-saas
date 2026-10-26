<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Rules\CustomerPhoneNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosOrderService
{
    public function __construct(
        private readonly DeliveryPricingService $deliveryPricing,
        private readonly ReservationAvailabilityService $reservationAvailability,
    ) {}

    /**
     * @param  array<string, mixed>  $cart
     * @return array{
     *     lines: array<int, array<string, mixed>>,
     *     subtotal: string,
     *     discount: string,
     *     tax: string,
     *     delivery_fee: string,
     *     total: string,
     *     delivery_error: string|null
     * }
     */
    public function quote(
        Restaurant $restaurant,
        array $cart,
        string $orderType,
        ?string $latitude,
        ?string $longitude,
    ): array {
        [$lines, $subtotalCents] = $this->buildLines($restaurant, $cart);
        $offer = $this->applicableOffer($restaurant, $subtotalCents);
        $discountCents = $offer ? $this->discountCents($offer, $subtotalCents) : 0;
        $taxCents = $this->taxCents($restaurant, $subtotalCents);
        $deliveryFeeCents = 0;
        $deliveryError = null;

        if ($orderType === 'delivery') {
            if (! is_numeric($latitude) || ! is_numeric($longitude)) {
                $deliveryError = 'أدخل إحداثيات موقع التوصيل لحساب النطاق والرسوم.';
            } else {
                $route = $this->deliveryPricing->resolveRoadRoute(
                    filled($restaurant->latitude) ? (float) $restaurant->latitude : 33.5138,
                    filled($restaurant->longitude) ? (float) $restaurant->longitude : 36.2765,
                    (float) $latitude,
                    (float) $longitude,
                    (float) config('services.osrm.detour_factor', 1.0),
                );
                $distance = $route['distance_km'];

                if ($distance > (float) $restaurant->delivery_radius_km) {
                    $deliveryError = 'موقع التوصيل خارج نطاق هذا المطعم.';
                } else {
                    $deliveryFeeCents = $this->toCents($this->deliveryPricing->feeForDistance(
                        $distance,
                        (float) $restaurant->base_delivery_fee,
                        (float) $restaurant->base_delivery_km,
                        (float) $restaurant->per_km_delivery_fee,
                    ));
                }
            }
        }

        return [
            'lines' => $lines,
            'subtotal' => $this->fromCents($subtotalCents),
            'discount' => $this->fromCents($discountCents),
            'tax' => $this->fromCents($taxCents),
            'delivery_fee' => $this->fromCents($deliveryFeeCents),
            'total' => $this->fromCents(max(0, $subtotalCents + $taxCents + $deliveryFeeCents - $discountCents)),
            'delivery_error' => $deliveryError,
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{product_id: int, quantity: int, variant_index: int|null, custom_price: string|null, notes: string}
     */
    public function newCartLine(Product $product, array $details): array
    {
        $variantIndex = null;
        if (isset($details['variant_index'])) {
            $variantIndex = filter_var($details['variant_index'], FILTER_VALIDATE_INT);

            if ($variantIndex === false || $variantIndex < 0) {
                throw ValidationException::withMessages([
                    'cart' => 'اختر خياراً صالحاً لهذا المنتج.',
                ]);
            }
        }

        $line = [
            'product_id' => (int) $product->id,
            'quantity' => 1,
            'variant_index' => $variantIndex,
            'custom_price' => isset($details['custom_price']) ? (string) $details['custom_price'] : null,
            'notes' => '',
        ];

        $resolved = $this->resolvePrice($product, $line);
        if ($product->price_type === 'open_price') {
            $line['custom_price'] = $this->fromCents($resolved['unit_price_cents']);
        }

        return $line;
    }

    /**
     * @param  array<string, mixed>  $cart
     * @param  array<string, mixed>  $checkout
     */
    public function createOrder(
        Restaurant $restaurant,
        User $cashier,
        array $cart,
        array $checkout,
        string $checkoutToken,
        bool $createInvoice = true,
    ): Order {
        if (! Str::isUuid($checkoutToken)) {
            throw ValidationException::withMessages([
                'checkout' => 'معرّف المحاولة غير صالح. أعد تحميل نقطة البيع.',
            ]);
        }

        try {
            $order = DB::transaction(function () use (
                $restaurant,
                $cart,
                $checkout,
                $checkoutToken,
                $createInvoice,
            ): Order {
                $lockedRestaurant = Restaurant::query()
                    ->whereKey($restaurant->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $existingOrder = $lockedRestaurant->orders()
                    ->where('checkout_token', $checkoutToken)
                    ->lockForUpdate()
                    ->first();

                if ($existingOrder) {
                    return $existingOrder;
                }

                if (is_string($checkout['customer_phone'] ?? null)) {
                    $checkout['customer_phone'] = trim($checkout['customer_phone']);
                }

                $validated = Validator::make($checkout, [
                    'order_type' => ['required', 'in:dine_in,takeaway,delivery'],
                    'table_id' => ['nullable', 'integer'],
                    'customer_name' => ['required', 'string', 'max:255'],
                    'customer_phone' => ['required', 'string', 'max:20', new CustomerPhoneNumber],
                    'address' => ['nullable', 'string', 'max:1000'],
                    'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                    'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                    'notes' => ['nullable', 'string', 'max:1000'],
                    'cash_received' => ['required', 'boolean'],
                ], [
                    'customer_phone.required' => 'رقم الهاتف مطلوب.',
                    'customer_phone.string' => 'أدخل رقم هاتف صالحاً بصيغة محلية أو دولية.',
                    'customer_phone.max' => 'يجب ألا يتجاوز رقم الهاتف 20 حرفاً.',
                ])->validate();

                if (! $lockedRestaurant->canAcceptOrders()) {
                    throw ValidationException::withMessages([
                        'checkout' => $lockedRestaurant->orderUnavailableMessage(),
                    ]);
                }

                $serviceAvailable = $validated['order_type'] === 'delivery'
                    ? $lockedRestaurant->deliveryFulfillmentAvailable()
                    : $lockedRestaurant->serviceEnabled($validated['order_type']);

                if (! $serviceAvailable) {
                    throw ValidationException::withMessages([
                        'order_type' => 'نوع الخدمة المحدد غير مفعّل لهذا المطعم.',
                    ]);
                }

                [$lines, $subtotalCents] = $this->buildLines($lockedRestaurant, $cart);

                if ($lines === []) {
                    throw ValidationException::withMessages([
                        'cart' => 'أضف منتجاً واحداً على الأقل قبل تسجيل الطلب.',
                    ]);
                }

                $table = null;
                if ($validated['order_type'] === 'dine_in') {
                    $table = $this->lockAvailableTable(
                        $lockedRestaurant,
                        (int) ($validated['table_id'] ?? 0),
                    );
                } elseif (! empty($validated['table_id'])) {
                    throw ValidationException::withMessages([
                        'table_id' => 'اختر طاولة لطلبات الصالة فقط.',
                    ]);
                }

                $delivery = $this->deliveryDetails($lockedRestaurant, $validated);
                $offer = $this->applicableOffer($lockedRestaurant, $subtotalCents, lock: true);
                $discountCents = $offer ? $this->discountCents($offer, $subtotalCents) : 0;
                $taxCents = $this->taxCents($lockedRestaurant, $subtotalCents);
                $deliveryFeeCents = $delivery['fee_cents'];
                $totalCents = max(0, $subtotalCents + $taxCents + $deliveryFeeCents - $discountCents);
                $cashReceived = (bool) $validated['cash_received'];

                if ($offer && $discountCents > 0) {
                    $offer->incrementUsage();
                }

                $order = $lockedRestaurant->orders()->create([
                    'restaurant_table_id' => $table?->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'delivery_address' => match ($validated['order_type']) {
                        'dine_in' => 'طاولة '.$table->number,
                        'takeaway' => 'استلام من المطعم',
                        default => $validated['address'],
                    },
                    'delivery_latitude' => $delivery['latitude'],
                    'delivery_longitude' => $delivery['longitude'],
                    'customer_lat' => $delivery['latitude'],
                    'customer_lng' => $delivery['longitude'],
                    'distance_km' => $delivery['distance_km'],
                    'route_source' => $delivery['route_source'],
                    'estimated_duration_minutes' => $delivery['duration_minutes'],
                    'delivery_type' => $validated['order_type'],
                    'delivery_city' => $lockedRestaurant->city,
                    'delivery_fee' => $this->fromCents($deliveryFeeCents),
                    'subtotal' => $this->fromCents($subtotalCents),
                    'tax_amount' => $this->fromCents($taxCents),
                    'discount' => $this->fromCents($discountCents),
                    'discount_amount' => $this->fromCents($discountCents),
                    'offer_id' => $discountCents > 0 ? $offer->id : null,
                    'coupon_discount' => '0.00',
                    'total_amount' => $this->fromCents($totalCents),
                    'final_amount' => $this->fromCents($totalCents),
                    'payment_method' => 'cash',
                    'payment_status' => $cashReceived ? 'verified' : 'pending',
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'pending',
                    'device_token' => Str::random(40),
                    'tracking_code' => 'POS-'.strtoupper(Str::random(8)),
                    'checkout_token' => $checkoutToken,
                ]);

                foreach ($lines as $line) {
                    $order->items()->create([
                        'product_id' => $line['product_id'],
                        'product_name' => $line['product_name'],
                        'quantity' => $line['quantity'],
                        'price' => $this->fromCents($line['unit_price_cents']),
                        'variant_name' => $line['variant_name'],
                        'notes' => $line['notes'] !== '' ? $line['notes'] : null,
                        'total' => $this->fromCents($line['total_cents']),
                    ]);
                }

                if ($createInvoice) {
                    $invoiceNumber = 'INV-'.now()->format('Y').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
                    $order->invoice()->create([
                        'restaurant_id' => $lockedRestaurant->id,
                        'invoice_number' => $invoiceNumber,
                        'subtotal' => $this->fromCents($subtotalCents),
                        'tax_amount' => $this->fromCents($taxCents),
                        'delivery_fee' => $this->fromCents($deliveryFeeCents),
                        'total_amount' => $this->fromCents($totalCents),
                        'final_amount' => $this->fromCents($totalCents),
                        'status' => $cashReceived ? 'paid' : 'pending',
                        'paid_at' => $cashReceived ? now() : null,
                    ]);
                }

                DB::afterCommit(static function () use ($order): void {
                    event(new OrderCreated($order));
                });

                return $order;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $order = $restaurant->orders()
                ->where('checkout_token', $checkoutToken)
                ->first();

            if (! $order) {
                throw $exception;
            }
        }

        if ($order->wasRecentlyCreated) {
            Log::info('Cashier created a POS order.', [
                'order_id' => $order->id,
                'restaurant_id' => $restaurant->id,
                'cashier_id' => $cashier->id,
                'payment_status' => $order->payment_status,
            ]);
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $cart
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function buildLines(Restaurant $restaurant, array $cart): array
    {
        if (count($cart) > 100) {
            throw ValidationException::withMessages([
                'cart' => 'لا يمكن أن يتجاوز الطلب 100 صنف.',
            ]);
        }

        $productIds = collect($cart)
            ->map(fn (mixed $line): int => is_array($line)
                ? (filter_var($line['product_id'] ?? null, FILTER_VALIDATE_INT) ?: 0)
                : 0)
            ->filter(fn (int $productId): bool => $productId > 0)
            ->unique()
            ->values();
        $products = $restaurant->products()
            ->where('is_available', true)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'cart' => 'أحد المنتجات لم يعد متاحاً لهذا المطعم. راجع الطلب قبل المتابعة.',
            ]);
        }

        $lines = [];
        $subtotalCents = 0;

        foreach ($cart as $cartKey => $line) {
            if (! is_array($line)) {
                throw ValidationException::withMessages([
                    'cart' => 'بيانات أحد أصناف الطلب غير صالحة.',
                ]);
            }

            $productId = filter_var($line['product_id'] ?? null, FILTER_VALIDATE_INT);
            $product = $productId === false ? null : $products->get($productId);
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);

            if ($productId === false || ! $product instanceof Product || ! $quantity || $quantity < 1 || $quantity > 999) {
                throw ValidationException::withMessages([
                    'cart' => 'تحقق من المنتجات والكميات قبل تسجيل الطلب.',
                ]);
            }

            $resolved = $this->resolvePrice($product, $line);
            $lineTotalCents = $resolved['unit_price_cents'] * $quantity;
            $notes = (string) ($line['notes'] ?? '');

            if (mb_strlen($notes) > 1000) {
                throw ValidationException::withMessages([
                    'cart' => 'ملاحظات الصنف يجب ألا تتجاوز 1000 حرف.',
                ]);
            }

            $lines[] = [
                'key' => (string) $cartKey,
                'product_id' => (int) $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price_cents' => $resolved['unit_price_cents'],
                'unit_price' => $this->fromCents($resolved['unit_price_cents']),
                'variant_name' => $resolved['variant_name'],
                'notes' => $notes,
                'total_cents' => $lineTotalCents,
                'total' => $this->fromCents($lineTotalCents),
            ];
            $subtotalCents += $lineTotalCents;
        }

        return [$lines, $subtotalCents];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array{unit_price_cents: int, variant_name: string|null}
     */
    private function resolvePrice(Product $product, array $line): array
    {
        if ($product->price_type === 'variant') {
            $variantIndex = filter_var($line['variant_index'] ?? null, FILTER_VALIDATE_INT);
            $variants = is_array($product->variants) ? $product->variants : [];
            $variant = $variantIndex !== false ? ($variants[$variantIndex] ?? null) : null;

            if (! is_array($variant) || ! isset($variant['name'], $variant['price'])) {
                throw ValidationException::withMessages([
                    'cart' => 'اختر خياراً صالحاً لهذا المنتج.',
                ]);
            }

            return [
                'unit_price_cents' => $this->toCents($variant['price']),
                'variant_name' => (string) $variant['name'],
            ];
        }

        if ($product->price_type === 'open_price') {
            $customPrice = $line['custom_price'] ?? null;
            $priceCents = is_numeric($customPrice) ? $this->toCents($customPrice) : -1;
            $minimumCents = $product->min_price !== null ? $this->toCents($product->min_price) : 0;
            $maximumCents = $product->max_price !== null ? $this->toCents($product->max_price) : PHP_INT_MAX;

            if ($priceCents < $minimumCents || $priceCents > $maximumCents) {
                throw ValidationException::withMessages([
                    'cart' => 'المبلغ المدخل لهذا المنتج خارج النطاق المسموح.',
                ]);
            }

            return ['unit_price_cents' => $priceCents, 'variant_name' => null];
        }

        if ($product->price_type !== 'fixed') {
            throw ValidationException::withMessages([
                'cart' => 'نوع تسعير المنتج غير مدعوم في نقطة البيع.',
            ]);
        }

        return [
            'unit_price_cents' => $this->toCents($product->price),
            'variant_name' => null,
        ];
    }

    private function lockAvailableTable(Restaurant $restaurant, int $tableId): RestaurantTable
    {
        $table = $restaurant->tables()
            ->whereKey($tableId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $table) {
            throw ValidationException::withMessages([
                'tableId' => 'اختر طاولة نشطة تابعة لهذا المطعم.',
            ]);
        }

        $duration = (int) $restaurant->getExperimentalOrderFeatures()['reservation_duration_minutes'];
        $availableTables = $this->reservationAvailability->availableTables(
            $restaurant,
            now(),
            1,
            $duration,
        );

        if (! $availableTables->contains('id', $table->id)) {
            throw ValidationException::withMessages([
                'tableId' => 'هذه الطاولة مشغولة أو محجوزة حالياً. اختر طاولة أخرى.',
            ]);
        }

        return $table;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{fee_cents: int, latitude: float|null, longitude: float|null, distance_km: float|null, route_source: string|null, duration_minutes: int|null}
     */
    private function deliveryDetails(Restaurant $restaurant, array $validated): array
    {
        if ($validated['order_type'] !== 'delivery') {
            return [
                'fee_cents' => 0,
                'latitude' => null,
                'longitude' => null,
                'distance_km' => null,
                'route_source' => null,
                'duration_minutes' => null,
            ];
        }

        if (! filled($validated['address'] ?? null)
            || ! is_numeric($validated['latitude'] ?? null)
            || ! is_numeric($validated['longitude'] ?? null)) {
            throw ValidationException::withMessages([
                'address' => 'أدخل العنوان وإحداثيات موقع التوصيل.',
            ]);
        }

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $route = $this->deliveryPricing->resolveRoadRoute(
            filled($restaurant->latitude) ? (float) $restaurant->latitude : 33.5138,
            filled($restaurant->longitude) ? (float) $restaurant->longitude : 36.2765,
            $latitude,
            $longitude,
            (float) config('services.osrm.detour_factor', 1.0),
        );
        $distance = $route['distance_km'];

        if ($distance > (float) $restaurant->delivery_radius_km) {
            throw ValidationException::withMessages([
                'latitude' => 'موقع التوصيل خارج نطاق هذا المطعم.',
            ]);
        }

        $fee = $this->deliveryPricing->feeForDistance(
            $distance,
            (float) $restaurant->base_delivery_fee,
            (float) $restaurant->base_delivery_km,
            (float) $restaurant->per_km_delivery_fee,
        );

        return [
            'fee_cents' => $this->toCents($fee),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_km' => round($distance, 2),
            'route_source' => $route['source'],
            'duration_minutes' => $route['duration_minutes'],
        ];
    }

    private function applicableOffer(
        Restaurant $restaurant,
        int $subtotalCents,
        bool $lock = false,
    ): ?Offer {
        $query = $restaurant->activeOffers()
            ->where('min_order_amount', '<=', $this->fromCents($subtotalCents))
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function discountCents(Offer $offer, int $subtotalCents): int
    {
        if ($subtotalCents < $this->toCents($offer->min_order_amount)) {
            return 0;
        }

        return min($subtotalCents, match ($offer->type) {
            'percentage' => $this->roundedRatio(
                $subtotalCents * $this->toCents($offer->value),
                10000,
            ),
            'fixed_amount' => $this->toCents($offer->value),
            default => 0,
        });
    }

    private function taxCents(Restaurant $restaurant, int $subtotalCents): int
    {
        return $this->roundedRatio(
            $subtotalCents * $this->toCents($restaurant->tax_percentage ?? 0),
            10000,
        );
    }

    private function roundedRatio(int $numerator, int $denominator): int
    {
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
    }

    private function toCents(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw ValidationException::withMessages([
                'cart' => 'تعذر قراءة قيمة سعرية صالحة.',
            ]);
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return ((int) $matches[1] * 100) + (int) $fraction;
    }

    private function fromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
