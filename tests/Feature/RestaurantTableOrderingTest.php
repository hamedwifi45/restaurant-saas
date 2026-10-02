<?php

use App\Events\OrderCreated;
use App\Events\OrderReadyForDelivery;
use App\Http\Resources\KdsOrderResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Event;

it('opens a table QR link and keeps the table in the customer session', function () {
    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->get(route('restaurant.table.menu', [$restaurant->slug, $table->qr_token]));

    $response->assertRedirect(route('restaurant.menu', $restaurant->slug));

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertOk()
        ->assertSee('الطاولة '.$table->number);

    expect(session('restaurant_table_id'))->toBe($table->id);
});

it('builds table QR links from the configured public application URL', function () {
    config(['app.url' => 'https://menu.example.test']);

    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    expect($table->qrUrl())->toStartWith('https://menu.example.test/')
        ->and($table->qrUrl())->toContain('/'.$restaurant->slug.'/table/'.$table->qr_token);
});

it('shows the current cart items and prices in the table menu summary', function () {
    $restaurant = Restaurant::factory()->create(['tax_percentage' => 15]);
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'name' => 'وجبة الاختبار',
        'price' => 50,
    ]);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withSession([
        'cart_'.$restaurant->id => [$product->id => ['qty' => 2]],
        'restaurant_table_id' => $table->id,
        'restaurant_table_restaurant_id' => $restaurant->id,
    ])->get(route('restaurant.menu', $restaurant->slug))
        ->assertOk()
        ->assertSee('وجبة الاختبار')
        ->assertSee('2 × 50.00')
        ->assertSee('115.00')
        ->assertSee('mini-cart-items')
        ->assertSee('2 عناصر');
});

it('renders Syrian phone validation and an immediate checkout processing overlay', function () {
    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [$product->id => ['qty' => 1]],
    ])->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('pattern="(09|\+?9639)\d{8}"', false)
        ->assertSee('id="checkout-processing"', false)
        ->assertSee('جارٍ معالجة طلبك...');
});

it('returns updated cart items and totals when a menu item is added asynchronously', function () {
    $restaurant = Restaurant::factory()->create(['tax_percentage' => 15]);
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'name' => 'وجبة AJAX',
        'price' => 40,
    ]);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('total', 80)
        ->assertJsonPath('grand_total', 92)
        ->assertJsonPath('items.0.name', 'وجبة AJAX')
        ->assertJsonPath('items.0.quantity', 2)
        ->assertJsonPath('items.0.subtotal', 80);
});

it('creates a dine-in order linked to the scanned table', function () {
    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'price' => 50,
    ]);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    Event::fake();

    $response = $this->withSession([
        'cart_'.$restaurant->id => [$product->id => ['qty' => 2]],
        'restaurant_table_id' => $table->id,
        'restaurant_table_restaurant_id' => $restaurant->id,
    ])->post(route('checkout.store', $restaurant->slug), [
        'name' => 'عميل الطاولة',
        'phone' => '+963930123456',
        'payment_method' => 'cash',
    ]);

    $order = Order::query()->latest('id')->firstOrFail();

    $response->assertRedirect(route('order.success', [$restaurant->slug, $order->tracking_code]));

    $this->get(route('order.success', [$restaurant->slug, $order->tracking_code]))
        ->assertOk()
        ->assertSee('عرض الفاتورة')
        ->assertSee('تتبع الطلب')
        ->assertSee('العودة إلى منيو الطاولة')
        ->assertSee('طلب الطاولة')
        ->assertSee($table->number);

    $this->assertDatabaseHas('orders', [
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
        'delivery_type' => 'dine_in',
        'delivery_fee' => 0,
    ]);

    Event::assertDispatched(OrderCreated::class);
    expect(session('order_device_tokens.'.$order->tracking_code))->toBe($order->device_token);
});

it('requires a valid Syrian phone number for a table order', function () {
    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $session = [
        'cart_'.$restaurant->id => [$product->id => ['qty' => 1]],
        'restaurant_table_id' => $table->id,
        'restaurant_table_restaurant_id' => $restaurant->id,
    ];

    $this->withSession($session)->post(route('checkout.store', $restaurant->slug), [
        'name' => 'عميل الطاولة',
        'payment_method' => 'cash',
    ])->assertSessionHasErrors('phone');

    $this->withSession($session)->post(route('checkout.store', $restaurant->slug), [
        'name' => 'عميل الطاولة',
        'phone' => '1234567890',
        'payment_method' => 'cash',
    ])->assertSessionHasErrors('phone');
});

it('does not narrow the customer phone column when null orders exist during rollback', function () {
    $order = Order::factory()->create(['customer_phone' => null]);
    $migration = require database_path('migrations/2026_09_23_020000_make_customer_phone_nullable.php');

    $migration->down();

    $this->assertModelExists($order->fresh());
    expect($order->fresh()->customer_phone)->toBeNull();
});

it('persists KDS display statuses as valid order statuses', function () {
    $restaurant = Restaurant::factory()->create();
    $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'tracking_code' => 'KDS-STATUS-TEST',
    ]);

    $this->withoutMiddleware()->actingAs($user)
        ->get(route('kitchen.display', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('pendingStatusUpdates', false);

    $this->withoutMiddleware()->actingAs($user)
        ->postJson(route('kitchen.orders.updateStatus', ['orderId' => $order->tracking_code]), ['status' => 'new'])
        ->assertSuccessful()
        ->assertJsonPath('status', 'new');

    expect($order->fresh()->status)->toBe('pending');

    $order->update(['status' => 'ready']);

    $this->withoutMiddleware()->actingAs($user)
        ->postJson(route('kitchen.orders.updateStatus', ['orderId' => $order->tracking_code]), ['status' => 'completed'])
        ->assertSuccessful()
        ->assertJsonPath('status', 'completed');

    expect($order->fresh()->status)->toBe('delivered');
});

it('serializes the table number and product name for the kitchen display', function () {
    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'number' => 'A12',
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
        'delivery_type' => 'dine_in',
    ]);
    Order_item::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'برجر خاص',
    ]);

    $payload = (new KdsOrderResource($order->load(['items', 'restaurantTable'])))->resolve();

    expect($payload['table_number'])->toBe('A12')
        ->and($payload['type'])->toBe('dine-in')
        ->and($payload['items']->first()['name'])->toBe('برجر خاص');
});

it('broadcasts an order only on its restaurant KDS channel', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);

    $channel = (new OrderCreated($order))->broadcastOn()[0];

    expect($channel->name)->toBe('private-restaurant.'.$restaurant->id.'.kds')
        ->and($channel->name)->not->toContain((string) $otherRestaurant->id);
});

it('queues order-created and order-ready broadcasts', function () {
    expect(is_subclass_of(OrderCreated::class, ShouldBroadcast::class))->toBeTrue()
        ->and(is_subclass_of(OrderReadyForDelivery::class, ShouldBroadcast::class))->toBeTrue();
});
