<?php

use App\Models\Driver;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\User;

it('allows an assigned driver to update a delivery location', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driver->id,
        'status' => 'ready',
        'delivery_latitude' => 33.5,
        'delivery_longitude' => 36.2,
    ]);

    $this->actingAs($driver)
        ->postJson(route('driver.location.update', [$restaurant->slug, $order]), [
            'latitude' => 33.51,
            'longitude' => 36.21,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($order->fresh())
        ->driver_latitude->toBe('33.5100000')
        ->driver_longitude->toBe('36.2100000')
        ->status->toBe('on_way');
});

it('redirects guests to login before authorizing driver panel routes', function () {
    $restaurant = Restaurant::factory()->create();

    $this->get(route('driver.orders', $restaurant->slug))
        ->assertRedirect(route('login'));
});

it('allows public order tracking only with its secure device token', function () {
    $restaurant = Restaurant::factory()->create();
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'on_way',
        'device_token' => str_repeat('a', 40),
        'delivery_latitude' => 33.5,
        'delivery_longitude' => 36.2,
        'driver_latitude' => 33.51,
        'driver_longitude' => 36.21,
    ]);

    $this->get(route('order.track', [$restaurant->slug, $order->tracking_code]))
        ->assertForbidden();
    $this->get(route('order.success', [$restaurant->slug, $order->tracking_code]))
        ->assertForbidden();

    $this->get(route('order.track', [
        'slug' => $restaurant->slug,
        'code' => $order->tracking_code,
        'token' => $order->device_token,
    ]))
        ->assertSuccessful()
        ->assertSee('تتبع المندوب مباشر 🚴‍♂️')
        ->assertDontSee('id="tracking-map"', false);

    $this->get(route('order.success', [
        'slug' => $restaurant->slug,
        'code' => $order->tracking_code,
        'token' => $order->device_token,
    ]))
        ->assertSuccessful();

    $this->getJson(route('order.track.location', [
        'slug' => $restaurant->slug,
        'code' => $order->tracking_code,
        'token' => $order->device_token,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('driver.latitude', '33.5100000')
        ->assertJsonPath('driver.longitude', '36.2100000');
});

it('allows guests with a secure device token to view driver tracking', function () {
    $restaurant = Restaurant::factory()->create();
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'on_way',
        'device_token' => str_repeat('b', 40),
    ]);

    $this->get(route('driver.order.tracking', [
        'slug' => $restaurant->slug,
        'order' => $order->tracking_code,
        'token' => $order->device_token,
    ]))
        ->assertSuccessful()
        ->assertSee($order->tracking_code);
});

it('renders sized maps and invalidates Leaflet dimensions on driver pages', function (string $themeFolder) {
    $theme = Theme::factory()->create(['folder_name' => $themeFolder]);
    $restaurant = Restaurant::factory()->create(['theme_id' => $theme->id]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driver->id,
        'status' => 'on_way',
        'delivery_latitude' => 33.5,
        'delivery_longitude' => 36.2,
    ]);
    $availableOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'ready',
        'delivery_type' => 'delivery',
        'delivery_latitude' => 33.6,
        'delivery_longitude' => 36.3,
    ]);

    $response = $this->actingAs($driver)
        ->get(route('driver.orders', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('leaflet.css')
        ->assertSee('h-64 w-full', false)
        ->assertSee('data-customer-lat="33.5000000"', false)
        ->assertSee('data-restaurant-lat=', false)
        ->assertSee('invalidateSize(), 300', false)
        ->assertSee(route('orders.accept', ['slug' => $restaurant->slug, 'order' => $availableOrder->id]), false)
        ->assertSee('name="_token"', false);

    if ($themeFolder !== 'oriental-theme') {
        $response->assertSee("typeof window.Echo !== 'undefined' && window.Echo", false);
    }

    if ($themeFolder !== 'oriental-theme') {
        $this->get(route('driver.order.tracking', [
            'slug' => $restaurant->slug,
            'order' => $order->tracking_code,
        ]))
            ->assertSuccessful()
            ->assertSee('leaflet.css')
            ->assertSee('min-height: 250px')
            ->assertSee('this.map.invalidateSize()', false);
    }
})->with([
    'burger theme' => 'burger-theme',
    'albasha theme' => 'albasha-burger',
    'classic theme' => 'classic-2-burger',
    'oriental theme' => 'oriental-theme',
]);

it('allows a driver to accept an available delivery order', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'delivery',
        'status' => 'ready',
        'driver_id' => null,
    ]);

    $this->actingAs($driver)
        ->postJson(route('orders.accept', ['slug' => $restaurant->slug, 'order' => $order->id]), [])
        ->assertSuccessful()
        ->assertJsonPath('message', 'تم استلام الطلب بنجاح.');

    expect($order->fresh())
        ->driver_id->toBe($driver->id)
        ->status->toBe('on_way');
});

it('shows only the driver orders and completes delivery transitions', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driver->id,
        'status' => 'on_way',
    ]);
    $availableOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'ready',
        'delivery_type' => 'delivery',
    ]);
    $otherDriverOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => User::factory()->create([
            'role' => 'driver',
            'restaurant_id' => $restaurant->id,
        ])->id,
        'status' => 'on_way',
    ]);

    $this->actingAs($driver)
        ->get(route('driver.orders', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee($order->tracking_code)
        ->assertSee($order->customer_name)
        ->assertSee($availableOrder->tracking_code)
        ->assertDontSee($otherDriverOrder->tracking_code);

    $this->actingAs($driver)
        ->get(route('driver.dashboard', $restaurant->slug))
        ->assertForbidden();

    $this->actingAs($driver)
        ->get(route('driver.order.tracking', [$restaurant->slug, $order->tracking_code]))
        ->assertSuccessful()
        ->assertSee('نظام تتبع الطلبات والتوصيل')
        ->assertSee($order->tracking_code)
        ->assertSee('وصلت إلى الموقع');

    $this->actingAs($driver)
        ->getJson(route('driver.order.show', [$restaurant->slug, $order]))
        ->assertSuccessful()
        ->assertJsonPath('tracking_code', $order->tracking_code)
        ->assertJsonPath('status', 'on_way');

    $this->actingAs($driver)
        ->postJson(route('driver.status.update', [$restaurant->slug, $order]), ['status' => 'delivered'])
        ->assertSuccessful()
        ->assertJsonPath('status', 'delivered');

    expect($order->fresh()->status)->toBe('delivered');
});

it('gives restaurant admins a delivery dashboard without access to driver actions', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $otherRestaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $owner = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    Driver::query()->create([
        'user_id' => $driver->id,
        'is_available' => true,
    ]);
    $availableDriver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    Driver::query()->create([
        'user_id' => $availableDriver->id,
        'is_available' => true,
    ]);
    $offlineDriver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    Driver::query()->create([
        'user_id' => $offlineDriver->id,
        'is_available' => false,
    ]);
    $activeOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driver->id,
        'status' => 'on_way',
        'device_token' => str_repeat('c', 40),
    ]);
    $availableOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'ready',
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'delivery',
        'status' => 'delivered',
    ]);

    $this->actingAs($owner)
        ->get(route('kitchen.display', $restaurant->slug))
        ->assertSuccessful();

    foreach (['burger-theme', 'albasha-burger', 'classic-2-burger', 'oriental-theme'] as $themeFolder) {
        $theme = Theme::factory()->create(['folder_name' => $themeFolder]);
        $restaurant->update(['theme_id' => $theme->id]);

        $this->get(route('driver.orders', $restaurant->slug))
            ->assertForbidden();

        $this->get(route('driver.dashboard', $restaurant->slug))
            ->assertSuccessful()
            ->assertSee('لوحة التوصيل والمندوبين')
            ->assertSee('طلبات التوصيل النشطة')
            ->assertSee('طلبات التوصيل المكتملة')
            ->assertSee('<span data-metric="active-delivery-orders">2</span>', false)
            ->assertSee('<span data-metric="completed-delivery-orders">1</span>', false)
            ->assertSee('<span data-metric="dispatched-delivery-orders">1</span>', false)
            ->assertSee($activeOrder->tracking_code)
            ->assertSee($driver->name)
            ->assertSee('في التوصيل')
            ->assertSee($availableDriver->name)
            ->assertSee('متاح')
            ->assertSee($offlineDriver->name)
            ->assertSee('غير متصل');

        $this->get(route('driver.order.tracking', [
            'slug' => $restaurant->slug,
            'order' => $activeOrder->tracking_code,
        ]))
            ->assertSuccessful()
            ->assertSee('token='.$activeOrder->device_token, false)
            ->assertDontSee('id="btnStartDelivery"', false)
            ->assertDontSee('id="btnArrived"', false)
            ->assertDontSee('id="btnDelivered"', false);
    }

    $this->getJson(route('driver.order.show', [$restaurant->slug, $activeOrder]))
        ->assertForbidden();

    $this->get(route('driver.orders', $otherRestaurant->slug))
        ->assertForbidden();
    $this->get(route('driver.dashboard', $otherRestaurant->slug))
        ->assertForbidden();

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($superAdmin)
        ->get(route('driver.dashboard', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee($activeOrder->tracking_code);

    $this->actingAs($owner);
    $this->postJson(route('orders.accept', [$restaurant->slug, $availableOrder]), [])
        ->assertForbidden();
    $this->postJson(route('driver.status.update', [$restaurant->slug, $activeOrder]), ['status' => 'delivered'])
        ->assertForbidden();
    $this->postJson(route('driver.location.update', [$restaurant->slug, $activeOrder]), [
        'latitude' => 33.51,
        'longitude' => 36.21,
    ])->assertForbidden();
    $this->postJson(route('driver.order.arrived', [$restaurant->slug, $activeOrder]))
        ->assertForbidden();

    expect($availableOrder->fresh()->driver_id)->toBeNull()
        ->and($availableOrder->fresh()->status)->toBe('ready')
        ->and($activeOrder->fresh()->status)->toBe('on_way');

    $trackingUrl = route('driver.order.tracking', [
        'slug' => $restaurant->slug,
        'order' => $activeOrder->tracking_code,
        'token' => $activeOrder->device_token,
    ]);

    $this->get(route('order.track', [
        'slug' => $restaurant->slug,
        'code' => $activeOrder->tracking_code,
    ]))
        ->assertSuccessful()
        ->assertSee('تتبع المندوب مباشر 🚴‍♂️')
        ->assertSee('href="'.$trackingUrl.'"', false)
        ->assertDontSee('id="tracking-map"', false);
});

it('conditionally shows the live tracking button in every theme', function (string $themeFolder, string $status, bool $isInTransit) {
    $theme = Theme::factory()->create(['folder_name' => $themeFolder]);
    $restaurant = Restaurant::factory()->create(['theme_id' => $theme->id]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => $status,
        'device_token' => str_repeat('d', 40),
        'delivery_latitude' => 33.5,
        'delivery_longitude' => 36.2,
    ]);

    $trackingUrl = route('driver.order.tracking', [
        'slug' => $restaurant->slug,
        'order' => $order->tracking_code,
        'token' => $order->device_token,
    ]);

    $response = $this->get(route('order.track', [
        'slug' => $restaurant->slug,
        'code' => $order->tracking_code,
        'token' => $order->device_token,
    ]))
        ->assertSuccessful()
        ->assertSee('في الطريق إليك');

    if ($isInTransit) {
        $response
            ->assertSee('تتبع المندوب مباشر 🚴‍♂️')
            ->assertSee('href="'.$trackingUrl.'"', false)
            ->assertDontSee('id="tracking-map"', false);
    } else {
        $response
            ->assertDontSee('تتبع المندوب مباشر 🚴‍♂️')
            ->assertSee('id="tracking-map"', false);
    }

    if ($isInTransit) {
        $this->get($trackingUrl)
            ->assertSuccessful()
            ->assertSee('token='.$order->device_token, false)
            ->assertDontSee('id="btnArrived"', false)
            ->assertDontSee('id="btnDelivered"', false);
    }
})->with([
    'burger theme out for delivery' => ['burger-theme', 'out_for_delivery', true],
    'burger theme in transit' => ['burger-theme', 'in_transit', true],
    'burger theme before delivery' => ['burger-theme', 'ready', false],
    'albasha theme out for delivery' => ['albasha-burger', 'out_for_delivery', true],
    'albasha theme in transit' => ['albasha-burger', 'in_transit', true],
    'albasha theme before delivery' => ['albasha-burger', 'ready', false],
    'classic theme out for delivery' => ['classic-2-burger', 'out_for_delivery', true],
    'classic theme in transit' => ['classic-2-burger', 'in_transit', true],
    'classic theme before delivery' => ['classic-2-burger', 'ready', false],
    'oriental theme out for delivery' => ['oriental-theme', 'out_for_delivery', true],
    'oriental theme in transit' => ['oriental-theme', 'in_transit', true],
    'oriental theme before delivery' => ['oriental-theme', 'ready', false],
]);

it('does not expose an unassigned order to a driver', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create(['role' => 'driver', 'restaurant_id' => $restaurant->id]);
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'ready']);

    $this->actingAs($driver)
        ->getJson(route('driver.order.show', [$restaurant->slug, $order]))
        ->assertForbidden();
});

it('does not allow a driver to update another restaurant order', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'driver_id' => $driver->id,
        'status' => 'ready',
    ]);

    $this->actingAs($driver)
        ->postJson(route('driver.location.update', [$otherRestaurant->slug, $order]), [
            'latitude' => 33.51,
            'longitude' => 36.21,
        ])
        ->assertForbidden();
});

it('stops accepting location updates after delivery', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driver->id,
        'status' => 'delivered',
    ]);

    $this->actingAs($driver)
        ->postJson(route('driver.location.update', [$restaurant->slug, $order]), [
            'latitude' => 33.51,
            'longitude' => 36.21,
        ])
        ->assertUnprocessable();
});
