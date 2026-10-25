<?php

use App\Events\OrderCreated;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('checkout persists an order its items and invoice together', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 33.5138,
        'longitude' => 36.2765,
        'base_delivery_fee' => 5,
        'tax_percentage' => 10,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 25,
    ]);
    $checkoutResponse = $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ])->get(route('checkout', $restaurant->slug));
    $checkoutResponse->assertSuccessful()
        ->assertSee('name="checkout_token"', false);
    preg_match('/name="checkout_token" value="([^"]+)"/', $checkoutResponse->getContent(), $checkoutTokenMatch);
    expect($checkoutTokenMatch)->toHaveKey(1);
    $checkoutToken = $checkoutTokenMatch[1];

    Event::fake([OrderCreated::class]);

    $this->post(route('checkout.store', $restaurant->slug), checkoutPersistencePayload($restaurant, $checkoutToken))
        ->assertRedirect();

    $order = Order::query()->with(['items', 'invoice'])->sole();
    $invoice = Invoice::query()->sole();
    $item = Order_item::query()->sole();

    expect($order->checkout_token)->toBe($checkoutToken)
        ->and($order->items)->toHaveCount(1)
        ->and($order->invoice->is($invoice))->toBeTrue()
        ->and($item->product_name)->toBe($product->name)
        ->and($item->quantity)->toBe(2)
        ->and((float) $item->price)->toBe(25.0)
        ->and((float) $item->total)->toBe(50.0)
        ->and((float) $invoice->subtotal)->toBe((float) $order->subtotal)
        ->and((float) $invoice->tax_amount)->toBe((float) $order->tax_amount)
        ->and((float) $invoice->final_amount)->toBe((float) $order->final_amount);

    Event::assertDispatchedTimes(OrderCreated::class, 1);
});

test('public checkout accepts trimmed international phone numbers', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    $payload = checkoutPersistencePayload($restaurant);
    $payload['phone'] = " \t+14155552671 \n";

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ])->post(route('checkout.store', $restaurant->slug), $payload)
        ->assertRedirect();

    expect(Order::query()->sole()->customer_phone)->toBe('+14155552671');
});

test('checkout rolls back the order and its items when an item insert fails', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    DB::statement("CREATE TRIGGER fail_order_item_insert BEFORE INSERT ON order_items BEGIN SELECT RAISE(ABORT, 'forced item insert failure'); END");

    try {
        expect(fn () => $this->withoutExceptionHandling()
            ->withSession([
                'cart_'.$restaurant->id => [
                    $product->id => [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->post(route('checkout.store', $restaurant->slug), checkoutPersistencePayload($restaurant)))
            ->toThrow(QueryException::class);
    } finally {
        DB::statement('DROP TRIGGER fail_order_item_insert');
    }

    expect(Order::query()->count())->toBe(0)
        ->and(Order_item::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0)
        ->and(session('cart_'.$restaurant->id))->toHaveKey($product->id);
});

test('checkout rolls back the order and items when invoice creation fails', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    DB::statement("CREATE TRIGGER fail_invoice_insert BEFORE INSERT ON invoices BEGIN SELECT RAISE(ABORT, 'forced invoice insert failure'); END");

    try {
        expect(fn () => $this->withoutExceptionHandling()
            ->withSession([
                'cart_'.$restaurant->id => [
                    $product->id => [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->post(route('checkout.store', $restaurant->slug), checkoutPersistencePayload($restaurant)))
            ->toThrow(QueryException::class);
    } finally {
        DB::statement('DROP TRIGGER fail_invoice_insert');
    }

    expect(Order::query()->count())->toBe(0)
        ->and(Order_item::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0)
        ->and(session('cart_'.$restaurant->id))->toHaveKey($product->id);
});

test('repeated checkout submissions with the same token return the existing order', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    $checkoutToken = (string) Str::uuid();
    $payload = checkoutPersistencePayload($restaurant, $checkoutToken);
    $session = [
        'cart_'.$restaurant->id => [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ];

    Event::fake([OrderCreated::class]);

    $firstResponse = $this->withSession($session)
        ->post(route('checkout.store', $restaurant->slug), $payload);
    $order = Order::query()->sole();

    $firstResponse->assertRedirect(route('order.success', [$restaurant->slug, $order->tracking_code]));

    $this->post(route('checkout.store', $restaurant->slug), $payload)
        ->assertRedirect(route('order.success', [$restaurant->slug, $order->tracking_code]));

    expect(Order::query()->count())->toBe(1)
        ->and(Order_item::query()->count())->toBe(1)
        ->and(Invoice::query()->count())->toBe(1);

    Event::assertDispatchedTimes(OrderCreated::class, 1);
});

test('replaying an old checkout token does not discard a newer cart', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);
    $orderedProduct = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    $newCartProduct = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    $checkoutToken = (string) Str::uuid();
    $payload = checkoutPersistencePayload($restaurant, $checkoutToken);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $orderedProduct->id => [
                'product_id' => $orderedProduct->id,
                'quantity' => 1,
            ],
        ],
    ])->post(route('checkout.store', $restaurant->slug), $payload)
        ->assertRedirect();

    $order = Order::query()->sole();

    $this->withSession([
        'cart_'.$restaurant->id => [
            $newCartProduct->id => [
                'product_id' => $newCartProduct->id,
                'quantity' => 2,
            ],
        ],
    ])->post(route('checkout.store', $restaurant->slug), $payload)
        ->assertRedirect(route('order.success', [$restaurant->slug, $order->tracking_code]));

    expect(Order::query()->count())->toBe(1)
        ->and(Order_item::query()->count())->toBe(1)
        ->and(session('cart_'.$restaurant->id))->toHaveKey($newCartProduct->id);
});

/**
 * @return array<string, mixed>
 */
function checkoutPersistencePayload(Restaurant $restaurant, ?string $checkoutToken = null): array
{
    return [
        'checkout_token' => $checkoutToken ?? (string) Str::uuid(),
        'name' => 'Persistence Test Customer',
        'phone' => '0930123456',
        'address' => 'Test delivery address',
        'latitude' => $restaurant->latitude,
        'longitude' => $restaurant->longitude,
        'payment_method' => 'cash',
    ];
}
