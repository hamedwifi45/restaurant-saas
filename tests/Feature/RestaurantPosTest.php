<?php

use App\Livewire\Restaurant\PointOfSale;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\PosOrderService;
use App\Services\ReservationAvailabilityService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

function posTestCashier(Restaurant $restaurant, string $role = 'cashier'): User
{
    return User::factory()->create([
        'restaurant_id' => $restaurant->id,
        'role' => $role,
        'is_active' => true,
    ]);
}

function posTestProduct(Restaurant $restaurant, array $attributes = []): Product
{
    $category = Category::factory()->create([
        'restaurant_id' => $restaurant->id,
    ]);

    return Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        ...$attributes,
    ]);
}

function posTestCheckoutDetails(array $overrides = []): array
{
    return [
        'order_type' => 'takeaway',
        'customer_name' => 'POS Customer',
        'customer_phone' => '0912345678',
        'address' => '',
        'latitude' => '',
        'longitude' => '',
        'notes' => '',
        'cash_received' => false,
        ...$overrides,
    ];
}

test('only active restaurant cashiers and administrators can access their own pos', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $cashier = posTestCashier($restaurant);

    $this->get(route('pos.index', $restaurant->slug))->assertRedirect(route('login'));

    $this->actingAs($cashier)
        ->get(route('pos.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertSeeLivewire(PointOfSale::class)
        ->assertSee('جلسة الكاشير')
        ->assertSee('الطلب الحالي')
        ->assertSee('ابحث عن صنف أو تصنيف')
        ->assertDontSee('عمليات المطعم')
        ->assertDontSee('شاشة المطبخ')
        ->assertDontSee('fi-sidebar', false)
        ->assertDontSee('fi-header', false);

    $this->get(route('pos.index', $otherRestaurant->slug))->assertForbidden();

    $customer = posTestCashier($restaurant, 'customer');
    $this->actingAs($customer)
        ->get(route('pos.index', $restaurant->slug))
        ->assertForbidden();

    $administrator = posTestCashier($restaurant, 'restaurant_admin');
    $this->actingAs($administrator)
        ->get(route('pos.index', $restaurant->slug))
        ->assertSuccessful();

    $this->get(route('filament.restaurant.resources.orders.index'))
        ->assertSuccessful()
        ->assertSee('fi-sidebar', false)
        ->assertSee('fi-header', false);
});

test('non-dine-in POS renders do not query reservation availability', function () {
    $restaurant = Restaurant::factory()->create();
    $cashier = posTestCashier($restaurant);
    $availability = Mockery::mock(ReservationAvailabilityService::class);
    $availability->shouldNotReceive('availableTables');
    app()->instance(ReservationAvailabilityService::class, $availability);

    Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->assertSee('الطلب الحالي');
});

test('POS phone input pattern matches server phone formats', function () {
    $restaurant = Restaurant::factory()->create();
    $cashier = posTestCashier($restaurant);

    Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->assertSee('pattern="\\s*(09[0-9]{8}|\\+?[1-9][0-9]{7,14})\\s*"', false)
        ->assertSee('رقماً محلياً مثل 09xxxxxxxx أو دولياً مثل +963930123456');
});

test('cashier checkout stores an authoritative order item and paid invoice', function () {
    $restaurant = Restaurant::factory()->create([
        'tax_percentage' => 10,
    ]);
    $product = posTestProduct($restaurant, ['price' => 25]);
    $cashier = posTestCashier($restaurant);

    Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->set('cart.'.$product->id.':f.custom_price', '0.01')
        ->call('updateQuantity', $product->id.':f', 2)
        ->set('customerName', 'POS Customer')
        ->set('customerPhone', '0912345678')
        ->set('cashReceived', true)
        ->assertSee('50.00')
        ->call('placeOrder')
        ->assertRedirect();

    $order = Order::query()->with(['items', 'invoice'])->sole();
    $item = Order_item::query()->sole();
    $invoice = Invoice::query()->sole();

    expect($order->delivery_type)->toBe('takeaway')
        ->and((float) $order->subtotal)->toBe(50.0)
        ->and((float) $order->tax_amount)->toBe(5.0)
        ->and((float) $order->final_amount)->toBe(55.0)
        ->and($order->payment_status)->toBe('verified')
        ->and($item->quantity)->toBe(2)
        ->and((float) $item->price)->toBe(25.0)
        ->and((float) $item->total)->toBe(50.0)
        ->and($invoice->status)->toBe('paid')
        ->and($invoice->paid_at)->not->toBeNull();
});

test('pos hides unavailable services and blocks delivery when no fulfillment provider is enabled', function () {
    $restaurant = Restaurant::factory()->create([
        'service_config' => [
            'delivery' => true,
            'own_delivery_drivers' => false,
            'external_delivery' => false,
            'dine_in' => false,
            'takeaway' => true,
        ],
    ]);
    $product = posTestProduct($restaurant);
    $cashier = posTestCashier($restaurant);

    Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->assertDontSee('wire:click="selectOrderType(\'delivery\')"', false)
        ->assertSee('wire:click="selectOrderType(\'takeaway\')"', false)
        ->call('selectOrderType', 'delivery')
        ->assertHasErrors('orderType')
        ->set('orderType', 'delivery')
        ->set('cart', [
            $product->id.':f' => [
                'product_id' => $product->id,
                'quantity' => 1,
                'variant_index' => null,
                'custom_price' => null,
                'notes' => '',
            ],
        ])
        ->set('customerName', 'POS Customer')
        ->set('customerPhone', '0912345678')
        ->call('placeOrder')
        ->assertHasErrors('order_type');

    expect(Order::query()->count())->toBe(0);
});

it('accepts supported customer phone formats and persists only trimmed surrounding whitespace', function (string $phone, string $expectedPhone) {
    $restaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant);
    $cashier = posTestCashier($restaurant);

    Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->set('customerName', 'Phone Format Customer')
        ->set('customerPhone', $phone)
        ->call('placeOrder')
        ->assertRedirect();

    expect(Order::query()->sole()->customer_phone)->toBe($expectedPhone);
})->with([
    'local Syrian number' => ['0912345678', '0912345678'],
    'local number with surrounding whitespace' => [" \t0912345678 \n", '0912345678'],
    'international with plus' => ['+963930123456', '+963930123456'],
    'international without plus' => ['963930123456', '963930123456'],
    'other international country code' => ['+14155552671', '+14155552671'],
    'longer international number' => ['442071838750', '442071838750'],
]);

it('rejects malformed and empty customer phone numbers with a field-specific Arabic message', function (string $phone) {
    $restaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant);
    $cashier = posTestCashier($restaurant);
    $expectedError = $phone === ''
        ? 'رقم الهاتف مطلوب.'
        : 'أدخل رقم هاتف صالحاً بصيغة محلية أو دولية.';

    Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->set('customerName', 'Invalid Phone Customer')
        ->set('customerPhone', $phone)
        ->call('placeOrder')
        ->assertHasErrors('customer_phone')
        ->assertSee($expectedError);

    expect(Order::query()->count())->toBe(0);
})->with([
    'alphabetic value' => 'call me',
    'too short' => '12345',
    'invalid country-code prefix' => '+0123456789',
    'local number with incorrect length' => '091234567',
    'embedded whitespace' => '09123 45678',
    'empty value' => '',
]);

test('variant and open prices are validated and stored from server-side product rules', function () {
    $restaurant = Restaurant::factory()->create();
    $variantProduct = posTestProduct($restaurant, [
        'price_type' => 'variant',
        'variants' => [
            ['name' => 'Small', 'price' => 12],
            ['name' => 'Large', 'price' => 18],
        ],
    ]);
    $openPriceProduct = posTestProduct($restaurant, [
        'price_type' => 'open_price',
        'min_price' => 5,
        'max_price' => 30,
    ]);
    $cashier = posTestCashier($restaurant);
    $service = app(PosOrderService::class);
    $order = $service->createOrder(
        $restaurant,
        $cashier,
        [
            'large' => [
                'product_id' => $variantProduct->id,
                'quantity' => 2,
                'variant_index' => 1,
                'custom_price' => '0.01',
            ],
            'custom' => [
                'product_id' => $openPriceProduct->id,
                'quantity' => 1,
                'custom_price' => '19.99',
            ],
        ],
        posTestCheckoutDetails(),
        (string) Str::uuid(),
    );
    $items = $order->items()->orderBy('id')->get();

    expect($items)->toHaveCount(2)
        ->and((float) $items[0]->price)->toBe(18.0)
        ->and($items[0]->variant_name)->toBe('Large')
        ->and((float) $items[1]->price)->toBe(19.99)
        ->and((float) $order->subtotal)->toBe(55.99);

    $component = Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->set('openPrices.'.$openPriceProduct->id, '19.9')
        ->call('addProduct', $openPriceProduct->id);
    $lineKey = array_key_first($component->get('cart'));

    expect($lineKey)->not->toContain('.');

    $component->set('cart.'.$lineKey.'.notes', 'No ice')
        ->assertSet('cart.'.$lineKey.'.notes', 'No ice')
        ->set('customerName', 'Open Price Customer')
        ->set('customerPhone', '0912345678')
        ->call('placeOrder')
        ->assertRedirect();

    expect(Order_item::query()->latest('id')->value('notes'))->toBe('No ice');
});

test('saved cashier drafts can be restored with item notes without placing an order', function () {
    $restaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant, ['price' => 16]);
    $cashier = posTestCashier($restaurant);
    $component = Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->set('cart.'.$product->id.':f.notes', 'No garlic sauce')
        ->set('customerName', 'Draft Customer')
        ->set('orderNotes', 'Call on arrival')
        ->set('draftName', 'Family meal')
        ->call('saveDraft');

    $draftsKey = 'pos_saved_drafts.'.$restaurant->id.'.'.$cashier->id;
    $drafts = session()->get($draftsKey);
    $draftId = array_key_first($drafts);

    $component->call('clearCart')
        ->call('addProduct', $product->id)
        ->call('restoreDraft', $draftId)
        ->assertSet('cart.'.$product->id.':f.notes', 'No garlic sauce')
        ->assertSet('customerName', 'Draft Customer')
        ->assertSet('orderNotes', 'Call on arrival')
        ->assertSee('استعادة هذه المسودة؟');

    expect(Order::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0)
        ->and(count(session()->get($draftsKey)))->toBe(1);

    $component->call('saveDraft');

    expect(count(session()->get($draftsKey)))->toBe(1);
});

test('notes stay attached to separate cart lines through quantity changes and checkout', function () {
    $restaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant, [
        'price_type' => 'variant',
        'variants' => [
            ['name' => 'Small', 'price' => 12],
            ['name' => 'Large', 'price' => 20],
        ],
    ]);
    $cashier = posTestCashier($restaurant);
    $smallKey = $product->id.':v:0';
    $largeKey = $product->id.':v:1';

    Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id, 0)
        ->call('addProduct', $product->id, 1)
        ->set('cart.'.$smallKey.'.notes', 'No onions')
        ->set('cart.'.$largeKey.'.notes', 'Extra crispy')
        ->call('updateQuantity', $smallKey, 2)
        ->set('customerName', 'Notes Customer')
        ->set('customerPhone', '0912345678')
        ->call('placeOrder')
        ->assertRedirect();

    $items = Order_item::query()->orderBy('variant_name')->get();

    expect($items)->toHaveCount(2)
        ->and($items->firstWhere('variant_name', 'Large')->notes)->toBe('Extra crispy')
        ->and($items->firstWhere('variant_name', 'Small')->notes)->toBe('No onions')
        ->and($items->firstWhere('variant_name', 'Small')->quantity)->toBe(2);
});

test('quick orders append server-priced restaurant products and report unavailable products', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $existingProduct = posTestProduct($restaurant, ['price' => 12]);
    $presetProduct = posTestProduct($restaurant, ['price' => 21]);
    $variantProduct = posTestProduct($restaurant, [
        'price_type' => 'variant',
        'variants' => [
            ['name' => 'Regular', 'price' => 15],
            ['name' => 'Large', 'price' => 24],
        ],
    ]);
    $openPriceProduct = posTestProduct($restaurant, [
        'price_type' => 'open_price',
        'min_price' => 5,
        'max_price' => 30,
    ]);
    $unavailableProduct = posTestProduct($restaurant, ['price' => 30, 'is_available' => false]);
    $foreignProduct = posTestProduct($otherRestaurant, ['price' => 99]);
    $cashier = posTestCashier($restaurant);

    $component = Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $existingProduct->id)
        ->set('cart.'.$existingProduct->id.':f.notes', 'Keep this note')
        ->call('applyQuickOrder', [
            ['product_id' => $existingProduct->id, 'quantity' => 2],
            ['product_id' => $presetProduct->id, 'quantity' => 3],
            ['product_id' => $variantProduct->id, 'variant_index' => 1, 'quantity' => 2],
            ['product_id' => $openPriceProduct->id, 'quantity' => 1],
            ['product_id' => $unavailableProduct->id, 'quantity' => 1],
            ['product_id' => $foreignProduct->id, 'quantity' => 1],
        ]);

    $cart = $component->get('cart');

    expect($cart[$existingProduct->id.':f']['quantity'])->toBe(3)
        ->and($cart[$existingProduct->id.':f']['notes'])->toBe('Keep this note')
        ->and($cart[$presetProduct->id.':f']['quantity'])->toBe(3)
        ->and($cart[$variantProduct->id.':v:1']['quantity'])->toBe(2)
        ->and(isset($cart[$unavailableProduct->id.':f']))->toBeFalse()
        ->and(isset($cart[$openPriceProduct->id.':o:500']))->toBeFalse()
        ->and(isset($cart[$foreignProduct->id.':f']))->toBeFalse()
        ->and($component->get('statusMessage'))->toContain($unavailableProduct->name)
        ->and($component->get('statusMessage'))->toContain('يتطلب إدخال السعر يدوياً')
        ->and(Order::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0);
});

test('repeated cashier submissions with the same token return one order', function () {
    $restaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant, ['price' => 18]);
    $cashier = posTestCashier($restaurant);
    $component = Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->set('customerName', 'Retry Customer')
        ->set('customerPhone', '0912345678');

    $component->call('placeOrder')->assertRedirect();
    $component->call('placeOrder')->assertRedirect();
    $order = Order::query()->with('invoice')->sole();

    expect(Order::query()->count())->toBe(1)
        ->and(Order_item::query()->count())->toBe(1)
        ->and(Invoice::query()->count())->toBe(1)
        ->and($order->payment_status)->toBe('pending')
        ->and($order->invoice->status)->toBe('pending');
});

test('unpaid cashier orders are available to the restaurant kitchen display', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $product = posTestProduct($restaurant);
    $cashier = posTestCashier($restaurant);
    $kitchenStaff = posTestCashier($restaurant, 'kitchen_staff');
    $order = app(PosOrderService::class)->createOrder(
        $restaurant,
        $cashier,
        ['fixed' => ['product_id' => $product->id, 'quantity' => 1]],
        posTestCheckoutDetails(),
        (string) Str::uuid(),
    );

    $this->actingAs($kitchenStaff)
        ->get(route('kitchen.display', $restaurant->slug))
        ->assertSuccessful()
        ->assertViewHas('orderCollection', fn ($orders): bool => $orders->contains('id', $order->id));
});

test('kitchen staff cannot skip a KDS order state', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $kitchenStaff = posTestCashier($restaurant, 'kitchen_staff');
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'pending',
        'payment_status' => 'verified',
    ]);

    $this->actingAs($kitchenStaff)
        ->postJson(route('kitchen.orders.updateStatus', $order->tracking_code), ['status' => 'ready'])
        ->assertStatus(409);

    expect($order->fresh()->status)->toBe('pending');

    $this->postJson(route('kitchen.orders.updateStatus', $order->tracking_code), ['status' => 'preparing'])
        ->assertSuccessful();
});

test('order and item writes roll back if an order item insert fails', function () {
    $restaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant);
    $cashier = posTestCashier($restaurant);
    $checkoutToken = (string) Str::uuid();
    DB::statement("CREATE TRIGGER fail_pos_item_insert BEFORE INSERT ON order_items BEGIN SELECT RAISE(ABORT, 'forced item insert failure'); END");

    try {
        expect(fn () => app(PosOrderService::class)->createOrder(
            $restaurant,
            $cashier,
            ['fixed' => ['product_id' => $product->id, 'quantity' => 1]],
            posTestCheckoutDetails(),
            $checkoutToken,
        ))->toThrow(QueryException::class);
    } finally {
        DB::statement('DROP TRIGGER fail_pos_item_insert');
    }

    expect(Order::query()->count())->toBe(0)
        ->and(Order_item::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0);
});

test('order and items roll back if invoice creation fails', function () {
    $restaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant);
    $cashier = posTestCashier($restaurant);
    $checkoutToken = (string) Str::uuid();
    DB::statement("CREATE TRIGGER fail_pos_invoice_insert BEFORE INSERT ON invoices BEGIN SELECT RAISE(ABORT, 'forced invoice insert failure'); END");

    try {
        expect(fn () => app(PosOrderService::class)->createOrder(
            $restaurant,
            $cashier,
            ['fixed' => ['product_id' => $product->id, 'quantity' => 1]],
            posTestCheckoutDetails(),
            $checkoutToken,
        ))->toThrow(QueryException::class);
    } finally {
        DB::statement('DROP TRIGGER fail_pos_invoice_insert');
    }

    expect(Order::query()->count())->toBe(0)
        ->and(Order_item::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0);
});

test('dine in checkout requires an available table belonging to the restaurant', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $product = posTestProduct($restaurant);
    $cashier = posTestCashier($restaurant);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $foreignTable = RestaurantTable::factory()->create(['restaurant_id' => $otherRestaurant->id]);
    $component = Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->call('selectOrderType', 'dine_in')
        ->set('customerName', 'Dine In Customer')
        ->set('customerPhone', '0912345678');

    $component->set('tableId', (string) $foreignTable->id)
        ->call('placeOrder')
        ->assertHasErrors('tableId');

    $component->set('tableId', (string) $table->id)
        ->call('placeOrder')
        ->assertRedirect();

    expect(Order::query()->sole()->restaurant_table_id)->toBe($table->id);
});

test('delivery checkout recomputes the fee and rejects destinations outside the restaurant radius', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 33.5138,
        'longitude' => 36.2765,
        'delivery_radius_km' => 2,
        'base_delivery_fee' => 4,
        'base_delivery_km' => 2,
        'per_km_delivery_fee' => 1,
    ]);
    $product = posTestProduct($restaurant, ['price' => 20]);
    $cashier = posTestCashier($restaurant);
    $component = Livewire::actingAs($cashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->call('selectOrderType', 'delivery')
        ->set('customerName', 'Delivery Customer')
        ->set('customerPhone', '0912345678')
        ->set('address', 'Near restaurant')
        ->set('latitude', '33.5138')
        ->set('longitude', '36.2765');

    $component->call('placeOrder')->assertRedirect();

    $order = Order::query()->sole();
    expect($order->delivery_type)->toBe('delivery')
        ->and((float) $order->delivery_fee)->toBe(4.0)
        ->and((float) $order->final_amount)->toBe(24.0);

    $anotherCashier = posTestCashier($restaurant);
    Livewire::actingAs($anotherCashier)
        ->test(PointOfSale::class, ['slug' => $restaurant->slug])
        ->call('addProduct', $product->id)
        ->call('selectOrderType', 'delivery')
        ->set('customerName', 'Out of Range Customer')
        ->set('customerPhone', '0912345678')
        ->set('address', 'Far away')
        ->set('latitude', '34.0')
        ->set('longitude', '36.2765')
        ->call('placeOrder')
        ->assertHasErrors('latitude');

    expect(Order::query()->count())->toBe(1);
});

test('receipt access is scoped to the cashier restaurant', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $cashier = posTestCashier($restaurant);
    $order = $restaurant->orders()->create([
        'customer_name' => 'Receipt Customer',
        'customer_phone' => '0912345678',
        'delivery_type' => 'takeaway',
        'subtotal' => 10,
        'total_amount' => 10,
        'final_amount' => 10,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'status' => 'pending',
        'tracking_code' => 'POS-RECEIPT',
    ]);

    $this->actingAs($cashier)
        ->get(route('pos.receipt', [$restaurant->slug, $order->tracking_code]))
        ->assertSuccessful()
        ->assertSee('POS-RECEIPT');

    $this->get(route('pos.receipt', [$otherRestaurant->slug, $order->tracking_code]))
        ->assertForbidden();
});
