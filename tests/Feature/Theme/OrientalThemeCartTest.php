<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('oriental cart quantity and removal requests return asynchronous responses', function () {
    $restaurant = Restaurant::factory()->create();
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 125,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->postJson(route('cart.update', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 3,
    ])->assertSuccessful()
        ->assertJson([
            'success' => true,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 3],
        ],
    ])->postJson(route('cart.remove', $restaurant->slug), [
        'product_id' => $product->id,
    ])->assertSuccessful()
        ->assertJson([
            'success' => true,
            'product_id' => $product->id,
        ]);
});

test('oriental checkout renders delivery choices and Syrian phone validation', function () {
    $theme = Theme::factory()->create([
        'folder_name' => 'oriental-theme',
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'tax_percentage' => 7.5,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('value="delivery"', false)
        ->assertSee('value="dine_in"', false)
        ->assertSee('pattern="(09|\\+?9639)\\d{8}"', false)
        ->assertSee('7.50%')
        ->assertSee('7.50');
});

test('oriental checkout stores the configured restaurant tax rate', function () {
    $restaurant = Restaurant::factory()->create([
        'delivery_fee' => 0,
        'tax_percentage' => 5.5,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    Event::fake();

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->post(route('checkout.store', $restaurant->slug), [
        'name' => 'Customer',
        'phone' => '0930123456',
        'address' => 'Delivery address',
        'latitude' => 24.7,
        'longitude' => 46.7,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $order = Order::query()->sole();

    expect((float) $order->tax_amount)->toBe(5.5)
        ->and((float) $order->total_amount)->toBe(105.5);
});

test('cart summary includes the restaurant delivery fee in the final total', function (string $themeFolder) {
    $theme = Theme::factory()->create([
        'folder_name' => $themeFolder,
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'delivery_fee' => 17.5,
        'tax_percentage' => 10,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->get(route('cart.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('17.50')
        ->assertSee('127.50');
})->with([
    'Albasha Burger' => 'albasha-burger',
    'Burger Theme' => 'burger-theme',
    'Classic Burger' => 'classic-2-burger',
    'Oriental Theme' => 'oriental-theme',
]);

test('cart add response includes restaurant delivery fee in the grand total', function () {
    $restaurant = Restaurant::factory()->create([
        'delivery_fee' => 12.5,
        'tax_percentage' => 10,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful()
        ->assertJsonPath('subtotal', 100)
        ->assertJsonPath('delivery_fee', 12.5)
        ->assertJsonPath('grand_total', 122.5);
});
