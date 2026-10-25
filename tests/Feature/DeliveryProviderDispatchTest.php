<?php

use App\Filament\Restaurant\Pages\RestaurantDeliverySettings;
use App\Models\Category;
use App\Models\DeliveryDispatch;
use App\Models\DeliveryProvider;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\User;
use App\Services\DeliveryDispatchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function createDeliveryDispatchScenario(array $serviceConfig = []): array
{
    $restaurant = Restaurant::factory()->create([
        'service_config' => array_replace([
            'delivery' => true,
            'own_delivery_drivers' => false,
            'external_delivery' => true,
        ], $serviceConfig),
    ]);
    $provider = DeliveryProvider::factory()->create();
    $restaurant->deliveryProviders()->attach($provider->getKey(), ['is_enabled' => true]);
    $actor = User::factory()->create([
        'restaurant_id' => $restaurant->id,
        'role' => 'restaurant_admin',
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'delivery',
        'driver_id' => null,
        'status' => 'ready',
        'delivery_fee' => 12,
        'subtotal' => 80,
        'total_amount' => 92,
        'final_amount' => 92,
    ]);

    return compact('restaurant', 'provider', 'actor', 'order');
}

function queueDeliveryDispatch(array $scenario): DeliveryDispatch
{
    return app(DeliveryDispatchService::class)->queueManualDispatch(
        $scenario['restaurant'],
        $scenario['order'],
        $scenario['provider'],
        $scenario['actor'],
        ['provider_fee' => '7.50'],
    );
}

it('queues manual delivery for a restaurant without internal drivers', function () {
    $scenario = createDeliveryDispatchScenario();

    expect($scenario['restaurant']->ownDeliveryDriversEnabled())->toBeFalse();

    $dispatch = queueDeliveryDispatch($scenario);

    expect($dispatch->status)->toBe(DeliveryDispatch::STATUS_QUEUED)
        ->and($dispatch->provider->is($scenario['provider']))->toBeTrue()
        ->and($scenario['order']->fresh()->driver_id)->toBeNull()
        ->and($scenario['order']->fresh()->status)->toBe('ready');

    $this->assertDatabaseHas('delivery_dispatch_events', [
        'delivery_dispatch_id' => $dispatch->id,
        'actor_id' => $scenario['actor']->id,
        'from_status' => null,
        'to_status' => DeliveryDispatch::STATUS_QUEUED,
    ]);
});

it('keeps internal driver acceptance available when enabled', function () {
    $restaurant = Restaurant::factory()->create([
        'service_config' => [
            'delivery' => true,
            'own_delivery_drivers' => true,
            'external_delivery' => false,
        ],
    ]);
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
        ->assertSuccessful();

    expect($order->fresh())
        ->driver_id->toBe($driver->id)
        ->status->toBe('on_way');
});

it('rejects driver acceptance when the restaurant driver service is disabled', function () {
    $restaurant = Restaurant::factory()->create([
        'service_config' => [
            'delivery' => true,
            'own_delivery_drivers' => false,
            'external_delivery' => true,
        ],
    ]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'delivery_type' => 'delivery',
        'status' => 'ready',
    ]);

    $this->actingAs($driver)
        ->postJson(route('orders.accept', ['slug' => $restaurant->slug, 'order' => $order->id]), [])
        ->assertForbidden();

    expect($order->fresh()->driver_id)->toBeNull();
});

it('rejects dispatch when delivery is disabled', function () {
    $scenario = createDeliveryDispatchScenario([
        'delivery' => false,
        'own_delivery_drivers' => true,
    ]);

    expect(fn () => queueDeliveryDispatch($scenario))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('delivery_dispatches', 0);

    $driver = User::factory()->create([
        'restaurant_id' => $scenario['restaurant']->id,
        'role' => 'driver',
    ]);

    $this->actingAs($driver)
        ->postJson(route('orders.accept', [
            'slug' => $scenario['restaurant']->slug,
            'order' => $scenario['order']->id,
        ]), [])
        ->assertForbidden();

    expect($scenario['order']->fresh()->driver_id)->toBeNull();
});

it('does not offer delivery without an enabled fulfillment mode', function () {
    $restaurant = Restaurant::factory()->create([
        'service_config' => [
            'delivery' => true,
            'own_delivery_drivers' => false,
            'external_delivery' => false,
        ],
    ]);

    expect($restaurant->deliveryFulfillmentAvailable())->toBeFalse();
});

it('rejects inactive and unassociated providers', function () {
    $scenario = createDeliveryDispatchScenario();
    $inactiveProvider = DeliveryProvider::factory()->create(['is_active' => false]);
    $scenario['restaurant']->deliveryProviders()->attach($inactiveProvider, ['is_enabled' => true]);
    $unassociatedProvider = DeliveryProvider::factory()->create();
    $service = app(DeliveryDispatchService::class);

    expect(fn () => $service->queueManualDispatch(
        $scenario['restaurant'],
        $scenario['order'],
        $inactiveProvider,
        $scenario['actor'],
        ['provider_fee' => '7.50'],
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->queueManualDispatch(
        $scenario['restaurant'],
        $scenario['order'],
        $unassociatedProvider,
        $scenario['actor'],
        ['provider_fee' => '7.50'],
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('delivery_dispatches', 0);
});

it('rejects dispatch for orders that are not ready', function () {
    $scenario = createDeliveryDispatchScenario();
    $scenario['order']->update(['status' => 'preparing']);

    expect(fn () => queueDeliveryDispatch($scenario))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('delivery_dispatches', 0);
});

it('rejects cross-restaurant order and provider assignment attempts', function () {
    $scenario = createDeliveryDispatchScenario();
    $foreignScenario = createDeliveryDispatchScenario();
    $service = app(DeliveryDispatchService::class);

    expect(fn () => $service->queueManualDispatch(
        $scenario['restaurant'],
        $foreignScenario['order'],
        $scenario['provider'],
        $scenario['actor'],
        ['provider_fee' => '7.50'],
    ))->toThrow(ModelNotFoundException::class);

    expect(fn () => $service->queueManualDispatch(
        $scenario['restaurant'],
        $scenario['order'],
        $scenario['provider'],
        $foreignScenario['actor'],
        ['provider_fee' => '7.50'],
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('delivery_dispatches', 0);
});

it('prevents duplicate active dispatches and rejects invalid status transitions', function () {
    $scenario = createDeliveryDispatchScenario();
    $dispatch = queueDeliveryDispatch($scenario);
    $service = app(DeliveryDispatchService::class);

    expect(fn () => queueDeliveryDispatch($scenario))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $scenario['restaurant'],
        $dispatch,
        $scenario['actor'],
        DeliveryDispatch::STATUS_PICKED_UP,
        'تم التأكيد مع الشركة',
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('delivery_dispatches', 1);

    $cancelled = $service->transition(
        $scenario['restaurant'],
        $dispatch,
        $scenario['actor'],
        DeliveryDispatch::STATUS_CANCELLED,
        'تعذر تنفيذ الإحالة',
    );
    $retry = queueDeliveryDispatch($scenario);

    expect($cancelled->status)->toBe(DeliveryDispatch::STATUS_CANCELLED)
        ->and($retry->status)->toBe(DeliveryDispatch::STATUS_QUEUED);

    $this->assertDatabaseCount('delivery_dispatches', 2);
});

it('records confirmed status changes and updates the existing order lifecycle', function () {
    $scenario = createDeliveryDispatchScenario();
    $dispatch = queueDeliveryDispatch($scenario);
    $service = app(DeliveryDispatchService::class);

    $dispatch = $service->transition(
        $scenario['restaurant'],
        $dispatch,
        $scenario['actor'],
        DeliveryDispatch::STATUS_ACCEPTED,
        'أكدت الشركة قبول الطلب',
    );
    $dispatch = $service->transition(
        $scenario['restaurant'],
        $dispatch,
        $scenario['actor'],
        DeliveryDispatch::STATUS_PICKED_UP,
        'أكدت الشركة استلام الطلب',
    );
    $dispatch = $service->transition(
        $scenario['restaurant'],
        $dispatch,
        $scenario['actor'],
        DeliveryDispatch::STATUS_DELIVERED,
        'أكد العميل استلام الطلب',
    );

    expect($dispatch->status)->toBe(DeliveryDispatch::STATUS_DELIVERED)
        ->and($scenario['order']->fresh()->status)->toBe('delivered');

    $this->assertDatabaseCount('delivery_dispatch_events', 4);
});

it('does not add the provider fee to the customer invoice or order total', function () {
    $scenario = createDeliveryDispatchScenario();
    $invoice = Invoice::factory()->create([
        'order_id' => $scenario['order']->id,
        'restaurant_id' => $scenario['restaurant']->id,
        'subtotal' => 80,
        'total_amount' => 92,
    ]);

    $dispatch = queueDeliveryDispatch($scenario);

    expect($dispatch->customer_delivery_fee)->toBe('12.00')
        ->and($dispatch->provider_fee)->toBe('7.50')
        ->and($scenario['order']->fresh()->delivery_fee)->toBe('12.00')
        ->and($scenario['order']->fresh()->final_amount)->toBe('92.00')
        ->and($invoice->fresh()->total_amount)->toBe('92.00');
});

it('preserves dispatch history when external delivery is disabled', function () {
    $scenario = createDeliveryDispatchScenario();
    $dispatch = queueDeliveryDispatch($scenario);

    $scenario['restaurant']->update([
        'service_config' => array_replace(
            $scenario['restaurant']->getServiceConfig(),
            ['external_delivery' => false],
        ),
    ]);
    $scenario['provider']->update(['is_active' => false]);
    $scenario['restaurant']->deliveryProviders()->detach($scenario['provider']->getKey());

    expect($dispatch->fresh()->status)->toBe(DeliveryDispatch::STATUS_QUEUED);

    $this->assertDatabaseHas('delivery_dispatches', [
        'id' => $dispatch->id,
        'delivery_provider_id' => $scenario['provider']->id,
    ]);
    $this->assertDatabaseHas('delivery_dispatch_events', [
        'delivery_dispatch_id' => $dispatch->id,
        'to_status' => DeliveryDispatch::STATUS_QUEUED,
    ]);
});

it('keeps table services independent and hides delivery when delivery is disabled', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'service_config' => [
            'delivery' => false,
            'own_delivery_drivers' => false,
            'external_delivery' => false,
            'dine_in' => true,
            'table_reservations' => true,
        ],
        'plan' => 'growth',
        'bank_details' => 'Test bank details',
    ]);
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
    ]);
    session(['cart_'.$restaurant->id => [$product->id => ['qty' => 1]]]);

    expect($restaurant->serviceEnabled('delivery'))->toBeFalse()
        ->and($restaurant->serviceEnabled('dine_in'))->toBeTrue()
        ->and($restaurant->reservationsEnabled())->toBeTrue();

    $this->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertDontSee('توصيل للمنزل')
        ->assertSee('حجز طاولة');
});

it('lets restaurant owners associate active providers and configure delivery modes', function () {
    $restaurant = Restaurant::factory()->create();
    $owner = User::factory()->create([
        'restaurant_id' => $restaurant->id,
        'role' => 'restaurant_admin',
    ]);
    $provider = DeliveryProvider::factory()->create();

    Livewire::actingAs($owner)
        ->test(RestaurantDeliverySettings::class)
        ->assertSee($provider->name)
        ->set('data.delivery', true)
        ->set('data.own_delivery_drivers', false)
        ->set('data.external_delivery', true)
        ->set('data.provider_ids', [$provider->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($restaurant->fresh()->serviceEnabled('delivery'))->toBeTrue()
        ->and($restaurant->fresh()->ownDeliveryDriversEnabled())->toBeFalse()
        ->and($restaurant->fresh()->externalDeliveryEnabled())->toBeTrue()
        ->and($restaurant->fresh()->eligibleDeliveryProviders()->whereKey($provider->id)->exists())->toBeTrue();
});

it('shows confirmed external delivery status without implying driver GPS tracking', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $scenario = createDeliveryDispatchScenario();
    $scenario['restaurant']->update(['theme_id' => $theme->id]);
    $scenario['order']->update(['device_token' => str_repeat('a', 40)]);
    $dispatch = queueDeliveryDispatch($scenario);
    $service = app(DeliveryDispatchService::class);
    $service->transition(
        $scenario['restaurant'],
        $dispatch,
        $scenario['actor'],
        DeliveryDispatch::STATUS_ACCEPTED,
        'أكدت الشركة قبول الطلب',
    );
    $service->transition(
        $scenario['restaurant'],
        $dispatch,
        $scenario['actor'],
        DeliveryDispatch::STATUS_PICKED_UP,
        'أكدت الشركة استلام الطلب',
    );

    $this->get(route('order.track', [
        'slug' => $scenario['restaurant']->slug,
        'code' => $scenario['order']->tracking_code,
        'token' => $scenario['order']->device_token,
    ]))
        ->assertSuccessful()
        ->assertSee('حالة شركة التوصيل')
        ->assertSee($scenario['provider']->name)
        ->assertSee($scenario['provider']->phone)
        ->assertSee($scenario['provider']->email)
        ->assertSee('استلمت الشركة الطلب')
        ->assertDontSee('تتبع المندوب مباشر');

    $this->getJson(route('order.track.location', [
        'slug' => $scenario['restaurant']->slug,
        'code' => $scenario['order']->tracking_code,
        'token' => $scenario['order']->device_token,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('delivery_dispatch.status', DeliveryDispatch::STATUS_PICKED_UP)
        ->assertJsonPath('delivery_dispatch.provider_name', $scenario['provider']->name)
        ->assertJsonPath('driver.latitude', null);
});

it('excludes active external dispatches from the internal driver queue', function () {
    $scenario = createDeliveryDispatchScenario([
        'own_delivery_drivers' => true,
    ]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $scenario['restaurant']->id,
    ]);
    $dispatch = queueDeliveryDispatch($scenario);

    $this->actingAs($driver)
        ->get(route('driver.orders', $scenario['restaurant']->slug))
        ->assertSuccessful()
        ->assertDontSee($scenario['order']->tracking_code);

    $this->postJson(route('orders.accept', [
        'slug' => $scenario['restaurant']->slug,
        'order' => $scenario['order']->id,
    ]), [])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'تمت إحالة هذا الطلب إلى شركة توصيل، ولا يمكن قبوله كسائق تابع للمطعم.');

    expect($dispatch->fresh()->status)->toBe(DeliveryDispatch::STATUS_QUEUED)
        ->and($scenario['order']->fresh()->driver_id)->toBeNull();
});
