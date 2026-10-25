<?php

use App\Models\Invoice;
use App\Models\KdsOrderReceipt;
use App\Models\LocalOrderSyncOutbox;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\LocalOrderOutboxService;
use Illuminate\Support\Str;

function localOperationsCashier(Restaurant $restaurant, string $role = 'cashier'): User
{
    return User::factory()->create([
        'restaurant_id' => $restaurant->id,
        'role' => $role,
        'is_active' => true,
    ]);
}

function localOperationsEnablePairing(Restaurant $restaurant): void
{
    config([
        'local_operations.enabled' => true,
        'local_operations.restaurant_id' => $restaurant->id,
        'local_operations.restaurant_slug' => $restaurant->slug,
        'local_operations.cloud_endpoint' => null,
    ]);
}

/**
 * @return array<string, mixed>
 */
function localOperationsOrderPayload(string $uuid, int $productId, array $overrides = []): array
{
    return [
        'idempotency_key' => $uuid,
        'items' => [
            [
                'product_id' => $productId,
                'quantity' => 2,
                'notes' => 'No garlic sauce',
                'price' => 0.01,
            ],
        ],
        'checkout' => [
            'order_type' => 'takeaway',
            'customer_name' => 'Local POS Customer',
            'customer_phone' => '0912345678',
            'address' => '',
            'latitude' => '',
            'longitude' => '',
            'notes' => 'Handle carefully',
            'cash_received' => false,
        ],
        'restaurant_id' => 999999,
        'status' => 'delivered',
        'total' => '0.01',
        ...$overrides,
    ];
}

it('durably accepts local orders once and replays the same logical order by UUID', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    localOperationsEnablePairing($restaurant);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 17,
        'is_available' => true,
    ]);
    $cashier = localOperationsCashier($restaurant);
    $uuid = (string) Str::uuid();
    $payload = localOperationsOrderPayload($uuid, $product->id);

    $first = $this->actingAs($cashier)
        ->postJson(route('local.orders.store', $restaurant->slug), $payload)
        ->assertCreated()
        ->assertJsonPath('data.client_order_uuid', $uuid)
        ->assertJsonPath('outbox_status', 'pending')
        ->assertJsonPath('data.total', '34.00');

    $this->postJson(route('local.orders.store', $restaurant->slug), $payload)
        ->assertSuccessful()
        ->assertJsonPath('created', false)
        ->assertJsonPath('data.tracking_code', $first->json('data.tracking_code'));

    $order = Order::query()->with('items')->sole();
    $outbox = LocalOrderSyncOutbox::query()->sole();

    expect($order->checkout_token)->toBe($uuid)
        ->and($order->status)->toBe('pending')
        ->and($order->final_amount)->toBe('34.00')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2)
        ->and($order->items->first()->notes)->toBe('No garlic sauce')
        ->and($outbox->event_uuid)->toBe($uuid)
        ->and($outbox->payload['client_order_uuid'])->toBe($uuid)
        ->and($outbox->payload['items'][0]['notes'])->toBe('No garlic sauce')
        ->and($outbox->status)->toBe('pending')
        ->and(Invoice::query()->count())->toBe(0);
});

it('rejects local intake for unpaired tenants, foreign products, and unauthenticated callers', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $otherRestaurant = Restaurant::factory()->create(['plan' => 'pro']);
    localOperationsEnablePairing($restaurant);
    $cashier = localOperationsCashier($restaurant);
    $foreignProduct = Product::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'is_available' => true,
    ]);
    $uuid = (string) Str::uuid();
    $payload = localOperationsOrderPayload($uuid, $foreignProduct->id);

    $this->actingAs($cashier)
        ->postJson(route('local.orders.store', $otherRestaurant->slug), $payload)
        ->assertNotFound();

    $this->postJson(route('local.orders.store', $restaurant->slug), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cart');

    auth()->logout();
    $this->postJson(route('local.orders.store', $restaurant->slug), $payload)
        ->assertUnauthorized();

    expect(Order::query()->count())->toBe(0)
        ->and(LocalOrderSyncOutbox::query()->count())->toBe(0);
});

it('requires valid staff authentication and prevents cashiers from using KDS acknowledgement', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    localOperationsEnablePairing($restaurant);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 8,
    ]);
    $cashier = localOperationsCashier($restaurant);
    $uuid = (string) Str::uuid();

    $this->actingAs($cashier)
        ->postJson(route('local.orders.store', $restaurant->slug), localOperationsOrderPayload($uuid, $product->id))
        ->assertCreated();

    $this->postJson(route('kitchen.orders.acknowledge', $restaurant->slug), [
        'client_order_uuids' => [$uuid],
    ])->assertForbidden();

    expect(KdsOrderReceipt::query()->count())->toBe(0);
});

it('delivers local orders to KDS and acknowledges only after a durable client receipt', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    localOperationsEnablePairing($restaurant);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 11,
    ]);
    $cashier = localOperationsCashier($restaurant);
    $kitchenStaff = localOperationsCashier($restaurant, 'kitchen_staff');
    $uuid = (string) Str::uuid();

    $this->actingAs($cashier)
        ->postJson(route('local.orders.store', $restaurant->slug), localOperationsOrderPayload($uuid, $product->id))
        ->assertCreated();

    $this->actingAs($kitchenStaff)
        ->getJson(route('kitchen.orders.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertJsonPath('data.0.client_order_uuid', $uuid)
        ->assertJsonPath('data.0.delivery_acknowledged', false)
        ->assertJsonPath('data.0.items.0.notes', 'No garlic sauce')
        ->assertJsonPath('data.0.items.0.qty', 2);

    $this->getJson(route('kitchen.orders.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
    expect(KdsOrderReceipt::query()->count())->toBe(0);

    $acknowledgement = route('kitchen.orders.acknowledge', $restaurant->slug);
    $this->postJson($acknowledgement, ['client_order_uuids' => [$uuid]])
        ->assertSuccessful()
        ->assertJsonPath('acknowledged', true);
    $this->postJson($acknowledgement, ['client_order_uuids' => [$uuid]])
        ->assertSuccessful();

    expect(KdsOrderReceipt::query()->count())->toBe(1);

    $this->getJson(route('kitchen.orders.index', $restaurant->slug))
        ->assertJsonPath('data.0.delivery_acknowledged', true);
});

it('keeps accepted orders locally when cloud synchronization fails and safely retries the outbox', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    localOperationsEnablePairing($restaurant);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 9,
    ]);
    $cashier = localOperationsCashier($restaurant);
    $uuid = (string) Str::uuid();

    $this->actingAs($cashier)
        ->postJson(route('local.orders.store', $restaurant->slug), localOperationsOrderPayload($uuid, $product->id))
        ->assertCreated();

    $outboxService = app(LocalOrderOutboxService::class);
    $claimed = $outboxService->claimDue();
    expect($claimed)->toHaveCount(1)
        ->and($claimed->first()->attempts)->toBe(1);

    $outboxService->recordFailure($claimed->first(), 'Cloud endpoint unavailable.');

    expect(Order::query()->count())->toBe(1)
        ->and(LocalOrderSyncOutbox::query()->sole()->status)->toBe('failed')
        ->and(LocalOrderSyncOutbox::query()->sole()->last_error)->toBe('Cloud endpoint unavailable.')
        ->and($outboxService->claimDue())->toBeEmpty();

    $this->travel(16)->seconds();
    $retry = $outboxService->claimDue();

    expect($retry)->toHaveCount(1)
        ->and($retry->first()->event_uuid)->toBe($uuid)
        ->and($retry->first()->attempts)->toBe(2)
        ->and(Order::query()->count())->toBe(1);
});
