<?php

use App\Models\Order;
use App\Models\Restaurant;
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

    $this->actingAs($driver)
        ->get(route('driver.orders', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee($order->tracking_code)
        ->assertSee($order->customer_name);

    $this->actingAs($driver)
        ->get(route('driver.dashboard', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('لوحة المندوب')
        ->assertSee($order->tracking_code);

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
