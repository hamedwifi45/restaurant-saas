<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;

it('accepts orders only during configured hours, including overnight schedules', function () {
    $restaurant = Restaurant::factory()->create([
        'opening_time' => '09:00',
        'closing_time' => '17:00',
    ]);

    $this->travelTo(now()->setTime(9, 0));
    expect($restaurant->canAcceptOrders())->toBeTrue();

    $this->travelTo(now()->setTime(17, 0));
    expect($restaurant->canAcceptOrders())->toBeFalse();

    $restaurant->update([
        'opening_time' => '22:00',
        'closing_time' => '02:00',
    ]);

    $this->travelTo(now()->setTime(23, 30));
    expect($restaurant->canAcceptOrders())->toBeTrue();

    $this->travelTo(now()->setTime(3, 0));
    expect($restaurant->canAcceptOrders())->toBeFalse();
});

it('shows the scheduled closure message and disables menu order buttons', function () {
    $restaurant = Restaurant::factory()->create([
        'opening_time' => '09:00',
        'closing_time' => '17:00',
    ]);
    $restaurant->theme()->update(['folder_name' => 'albasha-burger']);
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
    ]);

    $this->travelTo(now()->setTime(18, 0));

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('أوقات العمل من 09:00 إلى 17:00.')
        ->assertSee('disabled', false);
});

it('shows paused status and disables quick add on the example restaurant homepage', function () {
    $restaurant = Restaurant::factory()->create(['is_accepting_orders' => false]);
    $restaurant->theme()->update(['folder_name' => 'albasha-burger-example']);
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
    ]);

    $this->get(route('restaurant.home', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('الطلبات متوقفة حالياً')
        ->assertSee('disabled', false);
});

it('shows the busy message and rejects asynchronous cart additions while paused', function () {
    $restaurant = Restaurant::factory()->create([
        'is_accepting_orders' => false,
        'opening_time' => '09:00',
        'closing_time' => '17:00',
    ]);
    $restaurant->theme()->update(['folder_name' => 'albasha-burger']);
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
    ]);

    $this->travelTo(now()->setTime(12, 0));

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('المطعم يمر بفترة زحمة حالياً')
        ->assertSee('disabled', false);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertStatus(409)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', $restaurant->orderUnavailableMessage());
});

it('continues to accept cart additions while the restaurant is open', function () {
    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
    ]);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('count', 1);
});

it('rejects creating an order when restaurant availability is disabled', function () {
    $restaurant = Restaurant::factory()->create(['is_accepting_orders' => false]);

    $this->from(route('restaurant.menu', $restaurant->slug))
        ->post(route('checkout.store', $restaurant->slug), [])
        ->assertRedirect(route('restaurant.menu', $restaurant->slug))
        ->assertSessionHasErrors(['availability']);

    expect(Order::query()->count())->toBe(0);
});

it('rejects creating an order outside configured operating hours', function () {
    $restaurant = Restaurant::factory()->create([
        'opening_time' => '09:00',
        'closing_time' => '17:00',
    ]);

    $this->travelTo(now()->setTime(18, 0));
    $this->from(route('restaurant.menu', $restaurant->slug))
        ->post(route('checkout.store', $restaurant->slug), [])
        ->assertRedirect(route('restaurant.menu', $restaurant->slug))
        ->assertSessionHasErrors(['availability']);

    expect(Order::query()->count())->toBe(0);
});

it('shows the order availability toggle on the KDS screen', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $restaurant->theme()->update(['folder_name' => 'albasha-burger-example']);
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($user)
        ->get(route('kitchen.display', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة)')
        ->assertSee('استقبال الطلبات متاح');
});

it('allows assigned restaurant staff to toggle order acceptance', function () {
    $restaurant = Restaurant::factory()->create();
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($user)
        ->post(route('restaurant.orders.availability', $restaurant->slug), [
            'is_accepting_orders' => '0',
        ])
        ->assertRedirect();

    expect($restaurant->fresh()->is_accepting_orders)->toBeFalse();

    $this->postJson(route('restaurant.orders.availability', $restaurant->slug), [
        'is_accepting_orders' => true,
    ])->assertOk()
        ->assertJsonPath('is_accepting_orders', true);

    expect($restaurant->fresh()->is_accepting_orders)->toBeTrue();
});

it('does not allow staff from another restaurant to toggle availability', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $otherRestaurant->id,
    ]);

    $this->actingAs($user)
        ->post(route('restaurant.orders.availability', $restaurant->slug), [
            'is_accepting_orders' => '0',
        ])
        ->assertForbidden();

    expect($restaurant->fresh()->is_accepting_orders)->toBeTrue();
});
