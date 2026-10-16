<?php

use App\Events\OrderCreated;
use App\Events\ReservationUpdated;
use App\Filament\Restaurant\Pages\RestaurantReservations;
use App\Http\Resources\KdsOrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Observers\OrderObserver;
use App\Services\ReservationAvailabilityService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('dine-in reservation checkout is available only in the albasha burger theme', function () {
    $burgerTheme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $burgerRestaurant = Restaurant::factory()->create(['theme_id' => $burgerTheme->id]);
    $burgerProduct = Product::factory()->create(['restaurant_id' => $burgerRestaurant->id]);

    $this->withSession([
        'cart_'.$burgerRestaurant->id => [
            $burgerProduct->id => [
                'product_id' => $burgerProduct->id,
                'quantity' => 1,
                'unit_price' => $burgerProduct->price,
            ],
        ],
    ])->get(route('checkout', $burgerRestaurant->slug))
        ->assertSuccessful()
        ->assertDontSee('name="dine_in_arrival_at"', false)
        ->assertDontSee('حجز طاولة');

    $burgerRestaurant->update([
        'bank_details' => 'حساب التحويل: 123456',
        'experimental_order_features' => [
            ...Restaurant::EXPERIMENTAL_ORDER_FEATURE_DEFAULTS,
            'table_reservations' => true,
            'risk_acknowledged' => true,
        ],
    ]);

    $this->withSession([
        'cart_'.$burgerRestaurant->id => [
            $burgerProduct->id => [
                'product_id' => $burgerProduct->id,
                'quantity' => 1,
                'unit_price' => $burgerProduct->price,
            ],
        ],
    ])->get(route('checkout', $burgerRestaurant->slug))
        ->assertSuccessful()
        ->assertSee('name="dine_in_arrival_at"', false)
        ->assertSee('name="dine_in_guest_count"', false)
        ->assertSee('حجز طاولة');

    $otherTheme = Theme::factory()->create(['folder_name' => 'albasha-burger-example']);
    $otherRestaurant = Restaurant::factory()->create(['theme_id' => $otherTheme->id]);
    $otherProduct = Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $this->withSession([
        'cart_'.$otherRestaurant->id => [
            $otherProduct->id => [
                'product_id' => $otherProduct->id,
                'quantity' => 1,
                'unit_price' => $otherProduct->price,
            ],
        ],
    ])->get(route('checkout', $otherRestaurant->slug))
        ->assertSuccessful()
        ->assertDontSee('name="dine_in_arrival_at"', false)
        ->assertDontSee('حجز طاولة');

    $this->withSession([
        'cart_'.$otherRestaurant->id => [
            $otherProduct->id => [
                'product_id' => $otherProduct->id,
                'quantity' => 1,
                'unit_price' => $otherProduct->price,
            ],
        ],
    ])->post(route('checkout.store', $otherRestaurant->slug), [
        'name' => 'Test Customer',
        'phone' => '0930123456',
        'payment_method' => 'cash',
        'delivery_type' => 'dine_in',
    ])->assertSessionHasErrors(['latitude', 'longitude']);
});

test('burger dine-in reservations save arrival and guest preferences and appear in order views', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'tax_percentage' => 0,
        'base_delivery_fee' => 5,
        'bank_details' => 'اسم البنك: بنك الاختبار - رقم الحساب: 123456',
        'experimental_order_features' => [
            ...Restaurant::EXPERIMENTAL_ORDER_FEATURE_DEFAULTS,
            'table_reservations' => true,
            'reservation_duration_options' => [90, 120],
            'deposit_percentage' => 25,
            'reservation_seating_preference' => true,
            'reservation_occasion' => true,
            'reservation_high_chairs' => true,
            'reservation_stroller_space' => true,
            'reservation_accessibility_notes' => true,
            'reservation_allergy_notes' => true,
            'reservation_dietary_preference' => true,
            'reservation_confirmation_call' => true,
            'reservation_celebration_notes' => true,
            'risk_acknowledged' => true,
        ],
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    $table = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'seats' => 4,
        'is_active' => true,
    ]);

    Storage::fake('local');
    Event::fake();

    $arrivalAt = now()->addDay()->setTime(18, 30);

    $this->postJson(route('checkout.table-availability', $restaurant->slug), [
        'arrival_at' => $arrivalAt->format('Y-m-d\TH:i'),
        'guest_count' => 4,
        'duration_minutes' => 120,
    ])->assertSuccessful()
        ->assertJsonPath('tables.0.id', $table->id);

    $this->postJson(route('checkout.table-availability', $restaurant->slug), [
        'arrival_at' => $arrivalAt->format('Y-m-d\TH:i'),
        'guest_count' => 4,
        'duration_minutes' => 60,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('duration_minutes');

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
            ],
        ],
    ])->post(route('checkout.store', $restaurant->slug), [
        'name' => 'Test Customer',
        'phone' => '0930123456',
        'payment_method' => 'bank_transfer',
        'delivery_type' => 'dine_in',
        'restaurant_table_id' => $table->id,
        'dine_in_arrival_at' => $arrivalAt->format('Y-m-d\TH:i'),
        'dine_in_duration_minutes' => 120,
        'dine_in_guest_count' => 4,
        'dine_in_seating_preference' => 'window',
        'dine_in_occasion' => 'birthday',
        'dine_in_high_chair_count' => 1,
        'dine_in_stroller_space' => '1',
        'dine_in_accessibility_notes' => 'قرب المدخل',
        'dine_in_allergy_notes' => 'حساسية الفول السوداني',
        'dine_in_dietary_preference' => 'vegetarian',
        'dine_in_confirmation_call' => '1',
        'dine_in_celebration_notes' => 'شمعة عيد ميلاد',
        'payment_receipt' => UploadedFile::fake()->image('receipt.png'),
    ])->assertRedirect();

    $order = Order::query()->sole();

    expect($order->delivery_type)->toBe('dine_in')
        ->and($order->restaurant_table_id)->toBe($table->id)
        ->and($order->dine_in_arrival_at->format('Y-m-d H:i'))->toBe($arrivalAt->format('Y-m-d H:i'))
        ->and($order->dine_in_guest_count)->toBe(4)
        ->and($order->dine_in_duration_minutes)->toBe(120)
        ->and($order->dine_in_seating_preference)->toBe('window')
        ->and($order->dine_in_occasion)->toBe('birthday')
        ->and($order->dine_in_high_chair_count)->toBe(1)
        ->and($order->dine_in_stroller_space)->toBeTrue()
        ->and($order->dine_in_accessibility_notes)->toBe('قرب المدخل')
        ->and($order->dine_in_allergy_notes)->toBe('حساسية الفول السوداني')
        ->and($order->dine_in_dietary_preference)->toBe('vegetarian')
        ->and($order->dine_in_confirmation_call)->toBeTrue()
        ->and($order->dine_in_celebration_notes)->toBe('شمعة عيد ميلاد')
        ->and((float) $order->deposit_amount)->toBe(round((float) $order->total_amount * 0.25, 2))
        ->and($order->payment_status)->toBe('pending')
        ->and((float) $order->delivery_fee)->toBe(0.0)
        ->and($order->customer_lat)->toBeNull();

    Storage::disk('local')->assertExists($order->payment_receipt);
    Event::assertNotDispatched(OrderCreated::class);

    $this->postJson(route('checkout.table-availability', $restaurant->slug), [
        'arrival_at' => $arrivalAt->format('Y-m-d\TH:i'),
        'guest_count' => 4,
        'duration_minutes' => 120,
    ])->assertSuccessful()
        ->assertJsonCount(0, 'tables');

    $kdsOrder = (new KdsOrderResource($order))->toArray(request());

    expect($kdsOrder['dine_in_arrival_at'])->not->toBeNull()
        ->and($kdsOrder['dine_in_guest_count'])->toBe(4)
        ->and($kdsOrder['dine_in_allergy_notes'])->toBe('حساسية الفول السوداني')
        ->and($kdsOrder['dine_in_confirmation_call'])->toBeTrue();

    $successUrl = route('order.success', [$restaurant->slug, $order->tracking_code]).'?token='.$order->device_token;
    $this->get($successUrl)
        ->assertSuccessful()
        ->assertSee('تفاصيل حجز الطاولة')
        ->assertSee('بانتظار مراجعة المطعم')
        ->assertSee('حساسية الفول السوداني');

    $trackUrl = route('order.track', [$restaurant->slug, $order->tracking_code]).'?token='.$order->device_token;
    $this->get($trackUrl)
        ->assertSuccessful()
        ->assertSee('تفاصيل حجز الطاولة')
        ->assertSee('شمعة عيد ميلاد');

    $this->get(route('order.invoice', [$restaurant->slug, $order->tracking_code]))
        ->assertSuccessful()
        ->assertSee('تفاصيل حجز الطاولة')
        ->assertSee('موعد الوصول');
});

test('burger dine-in reservations require a future arrival and valid guest counts', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'bank_details' => 'تفاصيل التحويل: 123456',
        'experimental_order_features' => [
            ...Restaurant::EXPERIMENTAL_ORDER_FEATURE_DEFAULTS,
            'table_reservations' => true,
            'risk_acknowledged' => true,
        ],
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'seats' => 4,
        'is_active' => true,
    ]);
    $cartSession = [
        'cart_'.$restaurant->id => [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
            ],
        ],
    ];

    $this->withSession($cartSession)
        ->post(route('checkout.store', $restaurant->slug), [
            'name' => 'Test Customer',
            'phone' => '0930123456',
            'payment_method' => 'bank_transfer',
            'delivery_type' => 'dine_in',
            'dine_in_guest_count' => 4,
            'dine_in_arrival_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors(['dine_in_arrival_at']);

    $this->withSession($cartSession)
        ->post(route('checkout.store', $restaurant->slug), [
            'name' => 'Test Customer',
            'phone' => '0930123456',
            'payment_method' => 'bank_transfer',
            'delivery_type' => 'dine_in',
            'dine_in_guest_count' => 21,
            'dine_in_arrival_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors(['dine_in_guest_count']);

    expect(Order::query()->count())->toBe(0);
});

test('reservation availability respects saved duration after the order is fulfilled', function () {
    $restaurant = Restaurant::factory()->create([
        'experimental_order_features' => [
            ...Restaurant::EXPERIMENTAL_ORDER_FEATURE_DEFAULTS,
            'reservation_duration_options' => [30],
        ],
    ]);
    $occupiedTable = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'seats' => 4,
    ]);
    $availableTable = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'seats' => 4,
    ]);
    $arrivalAt = now()->addHour();

    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'dine_in',
        'restaurant_table_id' => $occupiedTable->id,
        'dine_in_arrival_at' => now()->addMinutes(30),
        'dine_in_duration_minutes' => 120,
        'reservation_status' => 'pending',
        'status' => 'delivered',
        'deposit_amount' => 20,
    ]);

    $availableTables = app(ReservationAvailabilityService::class)->availableTables(
        $restaurant,
        $arrivalAt,
        2,
        30,
    );

    expect($availableTables->modelKeys())->toBe([$availableTable->id]);
});

test('reservation availability excludes tables with active walk-in orders', function () {
    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'seats' => 4,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'dine_in',
        'restaurant_table_id' => $table->id,
        'status' => 'preparing',
    ]);
    $availability = app(ReservationAvailabilityService::class);

    expect($availability->availableTables($restaurant, now()->addDay(), 2, 90)->modelKeys())->toBe([]);

    $order->update(['status' => 'delivered']);

    expect($availability->availableTables($restaurant, now()->addDay(), 2, 90)->modelKeys())->toBe([$table->id]);
});

test('restaurant managers can approve a reservation and the customer receives confirmation', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'experimental_order_features' => [
            ...Restaurant::EXPERIMENTAL_ORDER_FEATURE_DEFAULTS,
            'table_reservations' => false,
        ],
    ]);
    $manager = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $table = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'seats' => 4,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'dine_in',
        'restaurant_table_id' => $table->id,
        'dine_in_arrival_at' => now()->addDay(),
        'dine_in_duration_minutes' => 120,
        'dine_in_guest_count' => 3,
        'reservation_status' => 'pending',
        'deposit_amount' => 20,
    ]);
    $laterReservation = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'dine_in',
        'restaurant_table_id' => $table->id,
        'dine_in_arrival_at' => now()->addDays(2),
        'dine_in_duration_minutes' => 90,
        'dine_in_guest_count' => 2,
        'reservation_status' => 'confirmed',
        'customer_name' => 'Later Reservation Guest',
    ]);
    $occupiedTable = RestaurantTable::factory()->create([
        'restaurant_id' => $restaurant->id,
        'seats' => 4,
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'dine_in',
        'restaurant_table_id' => $occupiedTable->id,
        'customer_name' => 'Walk-in Guest',
        'status' => 'preparing',
    ]);

    Event::fake();
    Notification::fake();

    Livewire::actingAs($manager)
        ->test(RestaurantReservations::class)
        ->assertSee('حالة الطاولات الآن')
        ->assertSee('مشغولة الآن')
        ->assertSee('Walk-in Guest')
        ->assertSee('Later Reservation Guest')
        ->assertSee('جدول الحجوزات')
        ->assertSee('اعتماد الحجز والعربون')
        ->assertSee('رفض الحجز')
        ->call('approveReservation', $order->id)
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->reservation_status)->toBe('confirmed')
        ->and($order->payment_status)->toBe('deposit_verified');

    Notification::assertSentTo(
        $order,
        OrderStatusUpdatedNotification::class,
    );

    expect($laterReservation->fresh()->reservation_status)->toBe('confirmed');
});

test('restaurant managers can reject pending reservations and release the table slot', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $restaurant = Restaurant::factory()->create(['theme_id' => $theme->id]);
    $manager = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
        'delivery_type' => 'dine_in',
        'dine_in_arrival_at' => now()->addDay(),
        'dine_in_duration_minutes' => 120,
        'reservation_status' => 'pending',
        'deposit_amount' => 20,
    ]);

    Notification::fake();

    Livewire::actingAs($manager)
        ->test(RestaurantReservations::class)
        ->call('rejectReservation', $order->id)
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->reservation_status)->toBe('rejected')
        ->and($order->payment_status)->toBe('rejected')
        ->and($order->status)->toBe('cancelled');

    Notification::assertSentTo($order, OrderStatusUpdatedNotification::class);
});

test('reservation dashboard initializes realtime updates and lists every booking when no date is selected', function () {
    $restaurant = Restaurant::factory()->create([
        'theme_id' => Theme::factory()->create(['folder_name' => 'albasha-burger'])->id,
    ]);
    $manager = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $first = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
        'delivery_type' => 'dine_in',
        'dine_in_arrival_at' => now()->addDays(2),
        'reservation_status' => 'pending',
        'customer_name' => 'First Schedule Customer',
    ]);
    $second = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
        'delivery_type' => 'dine_in',
        'dine_in_arrival_at' => now()->addDays(4),
        'reservation_status' => 'confirmed',
        'customer_name' => 'Second Schedule Customer',
    ]);

    $this->actingAs($manager)
        ->get('/restaurant/reservations')
        ->assertSuccessful()
        ->assertSee('window.EchoFactory')
        ->assertSee('.listen(\'.ReservationUpdated\'')
        ->assertSee('First Schedule Customer')
        ->assertSee('Second Schedule Customer');

    expect($first->dine_in_arrival_at->lessThan($second->dine_in_arrival_at))->toBeTrue();
});

test('demo data includes an enabled reservation dashboard with sample reservation requests', function () {
    $this->seed(DemoDataSeeder::class);

    $restaurant = Restaurant::query()->where('slug', 'qasr-al-sham')->firstOrFail();
    $manager = User::query()->where('email', 'pro@restaurant.com')->firstOrFail();

    expect($restaurant->theme->folder_name)->toBe('albasha-burger')
        ->and($restaurant->experimentalOrderFeatureEnabled('table_reservations'))->toBeTrue()
        ->and($restaurant->orders()->where('reservation_status', 'pending')->count())->toBe(2);

    $this->actingAs($manager)
        ->get('/restaurant/reservations')
        ->assertSuccessful()
        ->assertSee('DEMO-RES-0001')
        ->assertSee('سارة الخطيب')
        ->assertSee('اعتماد الحجز والعربون')
        ->assertSee('رفض الحجز');
});

test('reservation receipts stay private and are scoped to the restaurant', function () {
    Storage::fake('local');

    $restaurant = Restaurant::factory()->create();
    $manager = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $otherManager = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => Restaurant::factory()->create()->id,
    ]);
    $kitchenStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'payment_receipt' => 'payment-receipts/private-receipt.png',
    ]);
    Storage::disk('local')->put($order->payment_receipt, 'private receipt');

    $this->actingAs($manager)
        ->get(route('restaurant.orders.payment-receipt', $order))
        ->assertSuccessful()
        ->assertDownload();

    $this->actingAs($otherManager)
        ->get(route('restaurant.orders.payment-receipt', $order))
        ->assertForbidden();

    $this->actingAs($kitchenStaff)
        ->get(route('restaurant.orders.payment-receipt', $order))
        ->assertForbidden();
});

test('scheduled pickup requires a prepaid receipt and does not require delivery coordinates', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'base_delivery_fee' => 8,
        'bank_details' => 'حساب التحويل: 123456',
        'experimental_order_features' => [
            ...Restaurant::EXPERIMENTAL_ORDER_FEATURE_DEFAULTS,
            'scheduled_pickup' => true,
            'deposit_percentage' => 30,
            'risk_acknowledged' => true,
        ],
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    $pickupAt = now()->addDay()->setTime(12, 15);
    $cartSession = [
        'cart_'.$restaurant->id => [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
            ],
        ],
    ];

    Storage::fake('local');
    Event::fake();

    $this->withSession($cartSession)
        ->post(route('checkout.store', $restaurant->slug), [
            'name' => 'Pickup Customer',
            'phone' => '0930123456',
            'payment_method' => 'bank_transfer',
            'delivery_type' => 'takeaway',
            'requested_pickup_at' => $pickupAt->format('Y-m-d\TH:i'),
            'payment_receipt' => UploadedFile::fake()->image('deposit.png'),
        ])
        ->assertRedirect();

    $order = Order::query()->sole();

    expect($order->delivery_type)->toBe('takeaway')
        ->and($order->requested_pickup_at->format('Y-m-d H:i'))->toBe($pickupAt->format('Y-m-d H:i'))
        ->and($order->delivery_address)->toBe('استلام من المطعم')
        ->and($order->delivery_latitude)->toBeNull()
        ->and($order->delivery_longitude)->toBeNull()
        ->and((float) $order->delivery_fee)->toBe(0.0)
        ->and((float) $order->deposit_amount)->toBe(round((float) $order->total_amount * 0.30, 2))
        ->and($order->payment_status)->toBe('pending');

    Storage::disk('local')->assertExists($order->payment_receipt);
    Event::assertNotDispatched(OrderCreated::class);

    $this->get(route('order.success', [$restaurant->slug, $order->tracking_code]).'?token='.$order->device_token)
        ->assertSuccessful()
        ->assertSee('تفاصيل الاستلام من المطعم')
        ->assertSee('بانتظار مراجعة المطعم');
});

test('a confirmed deposit broadcasts the order to the kitchen', function () {
    $order = Order::factory()->create([
        'delivery_type' => 'dine_in',
        'dine_in_arrival_at' => now()->addDay(),
        'dine_in_duration_minutes' => 120,
        'reservation_status' => 'pending',
        'deposit_amount' => 20,
        'payment_status' => 'pending',
        'status' => 'pending',
    ]);

    Event::fake();

    $order->payment_status = 'deposit_verified';
    $order->reservation_status = 'confirmed';
    $order->syncChanges();

    DB::shouldReceive('afterCommit')
        ->twice()
        ->andReturnUsing(fn (Closure $callback) => $callback());

    (new OrderObserver)->updated($order);

    Event::assertDispatched(OrderCreated::class, fn (OrderCreated $event): bool => $event->order->is($order));
    Event::assertDispatchedTimes(ReservationUpdated::class, 1);
});
