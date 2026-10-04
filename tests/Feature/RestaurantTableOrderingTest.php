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
use App\Models\Theme;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

it('opens a table QR link and keeps the table in the customer session', function () {
    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->get(route('restaurant.table.menu', [$restaurant->slug, $table->qr_token]));

    $response->assertRedirect(route('restaurant.menu', $restaurant->slug));

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertOk()
        ->assertSee('طلب داخلي - الطاولة رقم '.$table->number);

    expect(session('restaurant_table_id'))->toBe($table->id);
});

it('accepts a restaurant table ID as a table entry URL', function () {
    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'number' => 'A12',
    ]);

    $this->get(route('restaurant.table.menu', [$restaurant->slug, $table->id]))
        ->assertRedirect(route('restaurant.menu', $restaurant->slug));

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('طلب داخلي - الطاولة رقم A12');
});

it('renders the full menu for a direct home-customer visit without table context', function () {
    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'name' => 'طبق التوصيل',
    ]);

    $this->get(route('restaurant.home', $restaurant->slug))
        ->assertSuccessful();

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee($category->name)
        ->assertSee('طبق التوصيل')
        ->assertDontSee('طلب داخلي - الطاولة رقم');

    expect(session('restaurant_table_id'))->toBeNull()
        ->and(session('table_id'))->toBeNull();
});

it('opens the menu with table context from a table-number query and keeps home visits working', function () {
    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'number' => '5',
    ]);

    $this->get(route('restaurant.home', $restaurant->slug).'?table=5')
        ->assertRedirect(route('restaurant.menu', $restaurant->slug));

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('طلب داخلي - الطاولة رقم 5');

    expect(session('restaurant_table_id'))->toBe($table->id);

    $this->get(route('restaurant.home', $restaurant->slug))
        ->assertSuccessful();

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('طلب داخلي - الطاولة رقم 5');
});

it('clears legacy serialized table values and renders the menu for home customers', function () {
    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'name' => 'طبق بعد تنظيف الجلسة',
    ]);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $this->withSession([
        'table' => $table,
        'table_id' => $table,
        'restaurant_table_id' => $table,
        'restaurant_table_restaurant_id' => $restaurant->id,
    ])->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('طبق بعد تنظيف الجلسة')
        ->assertDontSee('طلب داخلي - الطاولة رقم');

    expect(session()->has('table'))->toBeFalse()
        ->and(session()->has('table_id'))->toBeFalse()
        ->and(session()->has('restaurant_table_id'))->toBeFalse()
        ->and(session()->has('restaurant_table_restaurant_id'))->toBeFalse();
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

it('broadcasts an order only to its restaurant KDS and manager channels', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);

    Log::spy();

    $channelNames = collect((new OrderCreated($order))->broadcastOn())
        ->map(fn ($channel): string => $channel->name);

    expect($channelNames)
        ->toContain('private-restaurant.'.$restaurant->id.'.kds')
        ->toContain('private-restaurant.'.$restaurant->id.'.admin')
        ->toHaveCount(2)
        ->and($channelNames->filter(fn (string $name): bool => str_contains($name, (string) $otherRestaurant->id)))
        ->toBeEmpty();

    Log::shouldHaveReceived('info')
        ->once()
        ->with("📣 [Server Broadcast] Dispatching OrderCreated event for Order ID: {$order->id} on channel: restaurant.{$restaurant->id}.kds");
});

it('renders Reverb diagnostics in every themed kitchen display', function (string $themeFolder) {
    $theme = Theme::factory()->create(['folder_name' => $themeFolder]);
    $restaurant = Restaurant::factory()->create(['theme_id' => $theme->id]);
    $user = User::factory()->create(['restaurant_id' => $restaurant->id]);

    $this->withoutMiddleware()
        ->actingAs($user)
        ->get(route('kitchen.display', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('name="csrf-token" content="', false)
        ->assertSee('[KDS Reverb State]', false)
        ->assertSee('[KDS Reverb Subscribing to Private Channel]', false)
        ->assertSee('[KDS Reverb Subscription Auth Error]', false)
        ->assertSee('.listenToAll', false)
        ->assertSee('.OrderCreated', false);
})->with([
    'albasha theme' => 'albasha-burger',
    'burger theme' => 'burger-theme',
    'classic theme' => 'classic-2-burger',
    'oriental theme' => 'oriental-theme',
]);

it('authorizes KDS broadcast subscriptions only for staff in the restaurant', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $kitchenStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $restaurant->id,
    ]);
    $otherRestaurantStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $otherRestaurant->id,
    ]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $authorizer = Broadcast::getChannels()->get('restaurant.{restaurantId}.kds');

    expect($authorizer($kitchenStaff, $restaurant->id))->toBeTrue()
        ->and($authorizer($otherRestaurantStaff, $restaurant->id))->toBeFalse()
        ->and($authorizer($driver, $restaurant->id))->toBeFalse();
});

it('authorizes fleet broadcast subscriptions only for restaurant managers and kitchen staff', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $owner = User::factory()->create([
        'role' => 'restaurant_owner',
        'restaurant_id' => $restaurant->id,
    ]);
    $otherRestaurantOwner = User::factory()->create([
        'role' => 'restaurant_owner',
        'restaurant_id' => $otherRestaurant->id,
    ]);
    $kitchenStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $restaurant->id,
    ]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $authorizer = Broadcast::getChannels()->get('restaurant.{restaurantId}.admin');

    expect($authorizer($owner, $restaurant->id))->toBeTrue()
        ->and($authorizer($otherRestaurantOwner, $restaurant->id))->toBeFalse()
        ->and($authorizer($kitchenStaff, $restaurant->id))->toBeTrue()
        ->and($authorizer($driver, $restaurant->id))->toBeFalse()
        ->and($authorizer($superAdmin, $restaurant->id))->toBeTrue();
});

it('broadcasts new KDS orders immediately with a stable event name', function () {
    expect(is_subclass_of(OrderCreated::class, ShouldBroadcastNow::class))->toBeTrue()
        ->and((new OrderCreated(Order::factory()->make()))->broadcastAs())->toBe('OrderCreated')
        ->and(is_subclass_of(OrderReadyForDelivery::class, ShouldBroadcast::class))->toBeTrue();
});
