<?php

use App\Http\Resources\KdsOrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('dynamic cart prices and variants survive checkout and are saved on the order', function () {
    $theme = Theme::factory()->create([
        'folder_name' => 'albasha-burger',
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'tax_percentage' => 0,
        'latitude' => 33.5138,
        'longitude' => 36.2765,
        'base_delivery_fee' => 0,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Hummus',
        'price' => 100,
        'price_type' => 'variant',
        'variants' => [
            ['name' => 'ربع كيلو', 'price' => 500],
            ['name' => 'نصف كيلو', 'price' => 900],
        ],
    ]);
    $openPriceProduct = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Custom amount',
        'price' => 100,
        'price_type' => 'open_price',
        'min_price' => 500,
        'max_price' => 3000,
    ]);

    Event::fake();

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 1,
        'variant_index' => 99,
    ])->assertInvalid(['variant_index']);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 2,
        'variant_index' => 0,
    ])->assertSuccessful()
        ->assertJsonPath('subtotal', 1000);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 1,
        'variant_index' => 1,
    ])->assertSuccessful()
        ->assertJsonPath('subtotal', 1900);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $openPriceProduct->id,
        'quantity' => 1,
        'custom_amount' => 400,
    ])->assertInvalid(['custom_amount']);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $openPriceProduct->id,
        'quantity' => 1,
        'custom_amount' => 1800,
    ])->assertSuccessful()
        ->assertJsonPath('subtotal', 3700);

    $cart = session('cart_'.$restaurant->id);
    $variantCartKey = $product->id.'_v_0';
    $secondVariantCartKey = $product->id.'_v_1';
    $openPriceCartKey = $openPriceProduct->id.'_op_1800';

    expect($cart)->toHaveCount(3)
        ->and($cart[$variantCartKey]['unit_price'])->toBe(500.0)
        ->and($cart[$variantCartKey]['variant_name'])->toBe('ربع كيلو')
        ->and($cart[$secondVariantCartKey]['unit_price'])->toBe(900.0)
        ->and($cart[$secondVariantCartKey]['variant_name'])->toBe('نصف كيلو')
        ->and($cart[$openPriceCartKey]['unit_price'])->toBe(1800.0)
        ->and($cart[$openPriceCartKey]['variant_name'])->toBeNull();

    $this->postJson(route('cart.update', $restaurant->slug), [
        'cart_key' => $variantCartKey,
        'quantity' => 3,
    ])->assertSuccessful()
        ->assertJsonPath('cart_key', $variantCartKey);

    $this->get(route('cart.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('Hummus')
        ->assertSee('ربع كيلو')
        ->assertSee('نصف كيلو')
        ->assertSee('500');

    $this->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('Hummus (ربع كيلو)')
        ->assertSee('Hummus (نصف كيلو)')
        ->assertSee('500.00')
        ->assertSee('1,800.00');

    $this->post(route('checkout.store', $restaurant->slug), [
        'name' => 'Customer',
        'phone' => '0930123456',
        'address' => 'Delivery address',
        'latitude' => 33.5138,
        'longitude' => 36.2765,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $order = Order::query()->with('items')->sole();

    expect((float) $order->subtotal)->toBe(4200.0)
        ->and((float) $order->total_amount)->toBe(
            (float) $order->subtotal + (float) $order->tax_amount + (float) $order->delivery_fee
                - (float) $order->discount - (float) $order->coupon_discount
        )
        ->and($order->items)->toHaveCount(3);

    $variantOrderItem = $order->items->firstWhere('variant_name', 'ربع كيلو');
    $openPriceOrderItem = $order->items->firstWhere('product_id', $openPriceProduct->id);

    expect((float) $variantOrderItem->price)->toBe(500.0)
        ->and($variantOrderItem->quantity)->toBe(3)
        ->and((float) $variantOrderItem->total)->toBe(1500.0)
        ->and((float) $order->items->firstWhere('variant_name', 'نصف كيلو')->price)->toBe(900.0)
        ->and((float) $openPriceOrderItem->price)->toBe(1800.0)
        ->and((float) $openPriceOrderItem->total)->toBe(1800.0);

    $kdsOrder = (new KdsOrderResource($order))->toArray(request());
    $kdsVariant = collect($kdsOrder['items'])->firstWhere('variant_name', 'ربع كيلو');
    expect($kdsVariant['name'])->toBe('Hummus (ربع كيلو)')
        ->and($kdsVariant['unit_price'])->toBe(500.0);

    $this->get(route('order.track', [
        'slug' => $restaurant->slug,
        'code' => $order->tracking_code,
        'token' => $order->device_token,
    ]))->assertSuccessful()
        ->assertSee('Hummus (ربع كيلو)')
        ->assertSee('Hummus (نصف كيلو)')
        ->assertSee('500.00')
        ->assertSee('1,800.00');
});
