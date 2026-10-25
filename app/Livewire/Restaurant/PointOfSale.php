<?php

namespace App\Livewire\Restaurant;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\LocalOrderIntakeService;
use App\Services\PosOrderService;
use App\Services\ReservationAvailabilityService;
use App\Support\LocalRestaurantPairing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PointOfSale extends Component
{
    #[Locked]
    public string $slug = '';

    #[Locked]
    public string $checkoutToken = '';

    #[Locked]
    public ?string $activeDraftId = null;

    /** @var array<string, array<string, mixed>> */
    public array $cart = [];

    /** @var array<int|string, string|int|float|null> */
    public array $openPrices = [];

    public string $search = '';

    public string $selectedCategory = '';

    public string $orderType = 'takeaway';

    public ?string $tableId = null;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $address = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $orderNotes = '';

    public string $draftName = '';

    public bool $cashReceived = false;

    public string $statusMessage = '';

    public string $statusTone = 'success';

    private PosOrderService $orderService;

    private ReservationAvailabilityService $reservationAvailability;

    private LocalOrderIntakeService $localOrderIntake;

    private LocalRestaurantPairing $restaurantPairing;

    public function boot(
        PosOrderService $orderService,
        ReservationAvailabilityService $reservationAvailability,
        LocalOrderIntakeService $localOrderIntake,
        LocalRestaurantPairing $restaurantPairing,
    ): void {
        $this->orderService = $orderService;
        $this->reservationAvailability = $reservationAvailability;
        $this->localOrderIntake = $localOrderIntake;
        $this->restaurantPairing = $restaurantPairing;
    }

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $restaurant = $this->authorizedRestaurant();
        $cashier = $this->cashier();

        $checkoutToken = session()->get($this->tokenSessionKey());
        if (! is_string($checkoutToken) || ! Str::isUuid($checkoutToken)) {
            $this->checkoutToken = (string) Str::uuid();
            session()->put($this->tokenSessionKey(), $this->checkoutToken);
        } else {
            $this->checkoutToken = $checkoutToken;
        }

        $draft = session()->get($this->draftSessionKey($restaurant, $cashier), []);
        if (is_array($draft)) {
            $this->cart = is_array($draft['cart'] ?? null) ? $draft['cart'] : [];
            $this->openPrices = is_array($draft['open_prices'] ?? null) ? $draft['open_prices'] : [];
            $this->orderType = in_array($draft['order_type'] ?? null, ['dine_in', 'takeaway', 'delivery'], true)
                ? $draft['order_type']
                : 'takeaway';
            $this->tableId = isset($draft['table_id']) ? (string) $draft['table_id'] : null;
            $this->customerName = (string) ($draft['customer_name'] ?? '');
            $this->customerPhone = (string) ($draft['customer_phone'] ?? '');
            $this->address = (string) ($draft['address'] ?? '');
            $this->latitude = (string) ($draft['latitude'] ?? '');
            $this->longitude = (string) ($draft['longitude'] ?? '');
            $this->orderNotes = (string) ($draft['notes'] ?? '');
            $this->cashReceived = (bool) ($draft['cash_received'] ?? false);
            $this->activeDraftId = is_string($draft['draft_id'] ?? null) ? $draft['draft_id'] : null;
            $this->draftName = (string) ($draft['draft_name'] ?? '');
        }

        $availableOrderTypes = $this->availableOrderTypes($restaurant);
        if (! array_key_exists($this->orderType, $availableOrderTypes)) {
            $this->orderType = array_key_first($availableOrderTypes) ?? 'delivery';
            $this->tableId = null;
        }
    }

    public function updated(string $property): void
    {
        $this->validateCartNotes();
        $restaurant = $this->authorizedRestaurant();
        $this->persistDraft($restaurant, $this->cashier());
    }

    public function selectOrderType(string $orderType): void
    {
        $restaurant = $this->authorizedRestaurant();
        if (! in_array($orderType, ['dine_in', 'takeaway', 'delivery'], true)) {
            throw ValidationException::withMessages([
                'orderType' => 'نوع الطلب المحدد غير صالح.',
            ]);
        }

        $serviceAvailable = $orderType === 'delivery'
            ? $restaurant->deliveryFulfillmentAvailable()
            : $restaurant->serviceEnabled($orderType);

        if (! $serviceAvailable) {
            throw ValidationException::withMessages([
                'orderType' => 'نوع الخدمة المحدد غير مفعّل لهذا المطعم.',
            ]);
        }

        $this->orderType = $orderType;
        if ($orderType !== 'dine_in') {
            $this->tableId = null;
        }

        $this->persistDraft($this->authorizedRestaurant(), $this->cashier());
    }

    public function addProduct(mixed $productId, mixed $variantIndex = null): void
    {
        $restaurant = $this->authorizedRestaurant();
        $productId = filter_var($productId, FILTER_VALIDATE_INT);
        $variantIndex = $variantIndex === null
            ? null
            : filter_var($variantIndex, FILTER_VALIDATE_INT);

        if ($productId === false || $productId < 1 || ($variantIndex !== null && ($variantIndex === false || $variantIndex < 0))) {
            throw ValidationException::withMessages([
                'cart' => 'بيانات المنتج أو الخيار المحدد غير صالحة.',
            ]);
        }

        $product = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->findOrFail($productId);

        $line = $this->orderService->newCartLine($product, [
            'variant_index' => $variantIndex,
            'custom_price' => $product->price_type === 'open_price'
                ? ($this->openPrices[$productId] ?? null)
                : null,
        ]);
        $lineKey = match ($product->price_type) {
            'variant' => $productId.':v:'.$variantIndex,
            'open_price' => $productId.':o:'.str_replace('.', '', $line['custom_price']),
            default => $productId.':f',
        };

        if (isset($this->cart[$lineKey])) {
            $this->cart[$lineKey]['quantity'] = min(999, (int) $this->cart[$lineKey]['quantity'] + 1);
        } else {
            if (count($this->cart) >= 100) {
                throw ValidationException::withMessages([
                    'cart' => 'لا يمكن أن يتجاوز الطلب 100 صنف.',
                ]);
            }

            $this->cart[$lineKey] = $line;
        }

        $this->persistDraft($restaurant, $this->cashier());
    }

    public function updateQuantity(string $lineKey, int $quantity): void
    {
        $restaurant = $this->authorizedRestaurant();
        if (! isset($this->cart[$lineKey])) {
            throw ValidationException::withMessages([
                'cart' => 'لم يعد هذا الصنف موجوداً في الطلب.',
            ]);
        }

        if ($quantity < 1) {
            unset($this->cart[$lineKey]);
        } elseif ($quantity > 999) {
            throw ValidationException::withMessages([
                'cart' => 'الحد الأقصى لكمية الصنف هو 999.',
            ]);
        } else {
            $this->cart[$lineKey]['quantity'] = $quantity;
        }

        $this->persistDraft($restaurant, $this->cashier());
    }

    public function removeProduct(string $lineKey): void
    {
        $restaurant = $this->authorizedRestaurant();
        unset($this->cart[$lineKey]);
        $this->persistDraft($restaurant, $this->cashier());
    }

    public function saveDraft(): void
    {
        $restaurant = $this->authorizedRestaurant();
        $cashier = $this->cashier();
        $this->validateCartNotes();
        Validator::make(['name' => $this->draftName], [
            'name' => ['nullable', 'string', 'max:80'],
        ])->validate();
        $drafts = $this->savedDrafts($restaurant, $cashier);
        $draftId = $this->activeDraftId;

        if (! $draftId || ! isset($drafts[$draftId])) {
            $draftId = (string) Str::uuid();
        }

        $name = trim($this->draftName);
        if ($name === '') {
            $name = 'طلب '.now()->format('H:i').' · '.count($this->cart).' أصناف';
        }

        $drafts[$draftId] = [
            'name' => mb_substr($name, 0, 80),
            'saved_at' => now()->toIso8601String(),
            'data' => $this->draftData(),
        ];
        session()->put($this->savedDraftsSessionKey($restaurant, $cashier), $drafts);
        $this->activeDraftId = $draftId;
        $this->draftName = $drafts[$draftId]['name'];
        $this->persistDraft($restaurant, $cashier);
        $this->statusTone = 'success';
        $this->statusMessage = 'تم حفظ المسودة. يمكنك استعادتها من قائمة المسودات.';
    }

    public function restoreDraft(string $draftId): void
    {
        $restaurant = $this->authorizedRestaurant();
        $cashier = $this->cashier();
        $drafts = $this->savedDrafts($restaurant, $cashier);
        $draft = $drafts[$draftId] ?? null;

        abort_unless(is_array($draft) && is_array($draft['data'] ?? null), 404);

        $data = $draft['data'];
        $this->cart = is_array($data['cart'] ?? null) ? $data['cart'] : [];
        $this->openPrices = is_array($data['open_prices'] ?? null) ? $data['open_prices'] : [];
        $this->orderType = in_array($data['order_type'] ?? null, ['dine_in', 'takeaway', 'delivery'], true)
            ? $data['order_type']
            : 'takeaway';
        $this->tableId = isset($data['table_id']) ? (string) $data['table_id'] : null;
        $this->customerName = (string) ($data['customer_name'] ?? '');
        $this->customerPhone = (string) ($data['customer_phone'] ?? '');
        $this->address = (string) ($data['address'] ?? '');
        $this->latitude = (string) ($data['latitude'] ?? '');
        $this->longitude = (string) ($data['longitude'] ?? '');
        $this->orderNotes = (string) ($data['notes'] ?? '');
        $this->cashReceived = (bool) ($data['cash_received'] ?? false);
        $this->activeDraftId = $draftId;
        $this->draftName = (string) $draft['name'];
        $this->validateCartNotes();
        $this->checkoutToken = (string) Str::uuid();
        session()->put($this->tokenSessionKey(), $this->checkoutToken);
        $this->persistDraft($restaurant, $cashier);
        $this->statusTone = 'success';
        $this->statusMessage = 'تمت استعادة المسودة. لم يتم تسجيل أي طلب.';
    }

    public function applyQuickOrder(mixed $items): void
    {
        $restaurant = $this->authorizedRestaurant();
        $cashier = $this->cashier();
        $this->validateCartNotes();

        $validated = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.variant_index' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        $presetItems = $validated['items'];
        $productIds = collect($presetItems)->pluck('product_id')->unique()->values();
        $products = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $updatedCart = $this->cart;
        $unavailable = [];
        $added = 0;

        foreach ($presetItems as $item) {
            $product = $products->get((int) $item['product_id']);
            $variantIndex = $item['variant_index'] ?? null;

            if (! $product || ! $product->is_available) {
                $unavailable[] = $product?->name ?? 'منتج #'.$item['product_id'];

                continue;
            }

            if ($product->price_type === 'open_price') {
                $unavailable[] = $product->name.' (يتطلب إدخال السعر يدوياً)';

                continue;
            }

            if ($product->price_type === 'variant') {
                $variants = is_array($product->variants) ? $product->variants : [];
                $variant = $variantIndex === null ? null : ($variants[$variantIndex] ?? null);
                if (! is_array($variant) || ! isset($variant['name'], $variant['price'])) {
                    $unavailable[] = $product->name.' (خيار غير متاح)';

                    continue;
                }
            } elseif ($variantIndex !== null) {
                $unavailable[] = $product->name.' (خيار غير متاح)';

                continue;
            }

            $line = $this->orderService->newCartLine($product, [
                'variant_index' => $variantIndex,
            ]);
            $lineKey = $product->price_type === 'variant'
                ? $product->id.':v:'.$variantIndex
                : $product->id.':f';
            $quantity = (int) $item['quantity'];
            $currentQuantity = (int) ($updatedCart[$lineKey]['quantity'] ?? 0);

            if ($currentQuantity + $quantity > 999) {
                $unavailable[] = $product->name.' (تجاوز الحد الأقصى للكمية)';

                continue;
            }

            if (! isset($updatedCart[$lineKey]) && count($updatedCart) >= 100) {
                $unavailable[] = $product->name.' (تجاوز الحد الأقصى للأصناف)';

                continue;
            }

            if (isset($updatedCart[$lineKey])) {
                $updatedCart[$lineKey]['quantity'] = $currentQuantity + $quantity;
            } else {
                $line['quantity'] = $quantity;
                $updatedCart[$lineKey] = $line;
            }

            $added++;
        }

        $this->cart = $updatedCart;
        $this->persistDraft($restaurant, $cashier);
        $this->statusTone = $unavailable === [] ? 'success' : 'warning';
        $this->statusMessage = $unavailable === []
            ? 'تمت إضافة الطلب السريع إلى السلة.'
            : 'أضيفت '.$added.' أصناف. تعذر إضافة: '.implode('، ', $unavailable);
    }

    public function clearCart(): void
    {
        $restaurant = $this->authorizedRestaurant();
        $cashier = $this->cashier();
        $this->cart = [];
        $this->activeDraftId = null;
        $this->draftName = '';
        $this->persistDraft($restaurant, $cashier);
    }

    public function placeOrder(?string $clientOrderUuid = null): mixed
    {
        $restaurant = $this->authorizedRestaurant();
        $cashier = $this->cashier();
        $checkout = [
            'order_type' => $this->orderType,
            'table_id' => $this->tableId,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'notes' => $this->orderNotes,
            'cash_received' => $this->cashReceived,
        ];

        if ($this->restaurantPairing->isEnabled()) {
            abort_unless(is_string($clientOrderUuid) && Str::isUuid($clientOrderUuid), 422, 'معرّف الطلب المحلي غير صالح.');
            $this->checkoutToken = $clientOrderUuid;
            session()->put($this->tokenSessionKey(), $this->checkoutToken);
            $result = $this->localOrderIntake->accept(
                $restaurant,
                $cashier,
                $this->cart,
                $checkout,
                $clientOrderUuid,
            );
            $order = $result['order'];
            $this->dispatch('local-order-accepted', uuid: $clientOrderUuid);
        } else {
            $order = $this->orderService->createOrder(
                $restaurant,
                $cashier,
                $this->cart,
                $checkout,
                $this->checkoutToken,
            );
        }

        session()->forget([
            $this->draftSessionKey($restaurant, $cashier),
            $this->tokenSessionKey(),
        ]);
        if ($this->activeDraftId) {
            $drafts = $this->savedDrafts($restaurant, $cashier);
            unset($drafts[$this->activeDraftId]);
            session()->put($this->savedDraftsSessionKey($restaurant, $cashier), $drafts);
        }

        $hostRestaurant = request()->attributes->get('restaurant');

        if ($hostRestaurant instanceof Restaurant && $hostRestaurant->is($restaurant)) {
            return redirect()->route('pos.receipt', [
                'slug' => $restaurant->slug,
                'trackingCode' => $order->tracking_code,
            ]);
        }

        return redirect()->route('pos.receipt', [
            'slug' => $restaurant->slug,
            'trackingCode' => $order->tracking_code,
        ]);
    }

    public function render(): View
    {
        $restaurant = $this->authorizedRestaurant();
        $quote = $this->orderService->quote(
            $restaurant,
            $this->cart,
            $this->orderType,
            $this->latitude,
            $this->longitude,
        );

        return view('livewire.restaurant.point-of-sale', [
            'restaurant' => $restaurant,
            'availableOrderTypes' => $this->availableOrderTypes($restaurant),
            'categories' => $this->categories($restaurant),
            'products' => $this->products($restaurant),
            'tables' => $this->availableTables($restaurant),
            'drafts' => $this->savedDrafts($restaurant, $this->cashier()),
            'quote' => $quote,
        ]);
    }

    private function authorizedRestaurant(): Restaurant
    {
        Gate::authorize('access-pos', $this->slug);

        $restaurant = Restaurant::query()
            ->where('slug', $this->slug)
            ->whereKey(Auth::user()->restaurant_id)
            ->where('is_active', true)
            ->firstOrFail();
        $this->restaurantPairing->assertMatches($restaurant);

        $hostRestaurant = request()->attributes->get('restaurant');
        abort_unless(
            $hostRestaurant === null || ($hostRestaurant instanceof Restaurant && $hostRestaurant->is($restaurant)),
            403,
        );

        return $restaurant;
    }

    private function cashier(): User
    {
        $cashier = Auth::user();
        abort_unless($cashier instanceof User, 403);

        return $cashier;
    }

    /**
     * @return Collection<int, Category>
     */
    private function categories(Restaurant $restaurant): Collection
    {
        return Category::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function products(Restaurant $restaurant): Collection
    {
        return Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->with('category')
            ->where('is_available', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->when($this->selectedCategory !== '', fn ($query) => $query->where('category_id', $this->selectedCategory))
            ->when(trim($this->search) !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($productQuery) use ($search): void {
                    $productQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(150)
            ->get();
    }

    /**
     * @return Collection<int, RestaurantTable>
     */
    private function availableTables(Restaurant $restaurant): Collection
    {
        if ($this->orderType !== 'dine_in') {
            return new Collection;
        }

        $duration = (int) $restaurant->getExperimentalOrderFeatures()['reservation_duration_minutes'];

        return $this->reservationAvailability->availableTables($restaurant, now(), 1, $duration);
    }

    /**
     * @return array<string, string>
     */
    private function availableOrderTypes(Restaurant $restaurant): array
    {
        return array_filter([
            'dine_in' => 'صالة',
            'takeaway' => 'استلام',
            'delivery' => 'توصيل',
        ], fn (string $type): bool => $type === 'delivery'
            ? $restaurant->deliveryFulfillmentAvailable()
            : $restaurant->serviceEnabled($type), ARRAY_FILTER_USE_KEY);
    }

    private function persistDraft(Restaurant $restaurant, User $cashier): void
    {
        $data = $this->draftData();
        session()->put($this->draftSessionKey($restaurant, $cashier), [
            ...$data,
            'draft_id' => $this->activeDraftId,
            'draft_name' => $this->draftName,
            'updated_at' => now()->toIso8601String(),
        ]);

        if ($this->activeDraftId) {
            $drafts = $this->savedDrafts($restaurant, $cashier);
            if (isset($drafts[$this->activeDraftId])) {
                $drafts[$this->activeDraftId]['data'] = $data;
                $drafts[$this->activeDraftId]['saved_at'] = now()->toIso8601String();
                session()->put($this->savedDraftsSessionKey($restaurant, $cashier), $drafts);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function draftData(): array
    {
        return [
            'cart' => $this->cart,
            'open_prices' => $this->openPrices,
            'order_type' => $this->orderType,
            'table_id' => $this->tableId,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'notes' => $this->orderNotes,
            'cash_received' => $this->cashReceived,
        ];
    }

    private function validateCartNotes(): void
    {
        foreach ($this->cart as $lineKey => $line) {
            if (! is_array($line) || mb_strlen((string) ($line['notes'] ?? '')) > 1000) {
                throw ValidationException::withMessages([
                    'cart.'.$lineKey.'.notes' => 'يجب ألا تتجاوز ملاحظة الصنف 1000 حرف.',
                ]);
            }
        }
    }

    /**
     * @return array<string, array{name: string, saved_at: string, data: array<string, mixed>}>
     */
    private function savedDrafts(Restaurant $restaurant, User $cashier): array
    {
        $drafts = session()->get($this->savedDraftsSessionKey($restaurant, $cashier), []);

        return is_array($drafts) ? $drafts : [];
    }

    private function draftSessionKey(Restaurant $restaurant, User $cashier): string
    {
        return 'pos_draft.'.$restaurant->id.'.'.$cashier->id;
    }

    private function savedDraftsSessionKey(Restaurant $restaurant, User $cashier): string
    {
        return 'pos_saved_drafts.'.$restaurant->id.'.'.$cashier->id;
    }

    private function tokenSessionKey(): string
    {
        return 'pos_checkout_token.'.$this->slug.'.'.Auth::id();
    }
}
