<?php

use App\Filament\Restaurant\Resources\Orders\Pages\ListOrders;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function deliveryCheckoutPayload(float $latitude, float $longitude): array
{
    return [
        'name' => 'Customer Name',
        'phone' => '0912345678',
        'address' => 'Delivery address',
        'latitude' => $latitude,
        'longitude' => $longitude,
        'payment_method' => 'cash',
    ];
}

function seedDeliveryCheckout(Restaurant $restaurant): Product
{
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'price' => 20,
    ]);

    session(['cart_'.$restaurant->id => [$product->id => ['qty' => 1]]]);

    return $product;
}

function deliveryCheckoutThemeFolders(): array
{
    $checkoutTemplates = glob(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'cart'.DIRECTORY_SEPARATOR.'checkout.blade.php') ?: [];

    return array_map(
        fn (string $template): string => basename(dirname(dirname($template))),
        $checkoutTemplates,
    );
}

it('blocks checkout when the delivery location is outside the restaurant radius', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 0,
        'longitude' => 0,
        'delivery_radius_km' => 5,
    ]);
    seedDeliveryCheckout($restaurant);

    $this->from(route('checkout', $restaurant->slug))
        ->post(route('checkout.store', $restaurant->slug), deliveryCheckoutPayload(0, 0.1))
        ->assertRedirect(route('checkout', $restaurant->slug))
        ->assertSessionHasErrors('latitude');

    $this->postJson(route('checkout.store', $restaurant->slug), deliveryCheckoutPayload(0, 0.1))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('latitude');

    expect(Order::query()->where('restaurant_id', $restaurant->id)->exists())->toBeFalse();
});

it('uses safe fallback restaurant coordinates and still enforces the delivery radius', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => null,
        'longitude' => null,
        'delivery_radius_km' => 5,
    ]);
    seedDeliveryCheckout($restaurant);

    $this->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('data-restaurant-latitude="33.5138"', false)
        ->assertSee('data-restaurant-longitude="36.2765"', false)
        ->assertSee('restaurantLatitude: 33.5138', false)
        ->assertSee('restaurantLongitude: 36.2765', false)
        ->assertSee(':disabled="processing || isOutOfRange"', false)
        ->assertSee('window.checkoutPricingState?.setCustomerLocation(latitude, longitude)', false)
        ->assertDontSee('جاري التحديد...', false);

    $this->postJson(route('checkout.store', $restaurant->slug), deliveryCheckoutPayload(0, 0))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('latitude');

    expect(Order::query()->where('restaurant_id', $restaurant->id)->exists())->toBeFalse();
});

it('calculates distance-based delivery pricing from manually submitted coordinates', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 0,
        'longitude' => 0,
        'delivery_radius_km' => 20,
        'base_delivery_fee' => 2,
        'base_delivery_km' => 2,
        'per_km_delivery_fee' => 0.5,
        'tax_percentage' => 10,
    ]);
    seedDeliveryCheckout($restaurant);

    $this->post(route('checkout.store', $restaurant->slug), deliveryCheckoutPayload(0, 0.1))
        ->assertRedirect();

    $order = Order::query()->where('restaurant_id', $restaurant->id)->firstOrFail();

    expect((float) $order->distance_km)->toBe(11.12)
        ->and((float) $order->delivery_fee)->toBe(6.56)
        ->and((float) $order->total_amount)->toBe(28.56)
        ->and((float) $order->customer_lat)->toBe(0.0)
        ->and((float) $order->customer_lng)->toBe(0.1);
});

it('renders live checkout pricing and geofence controls for each customer theme', function (string $themeFolder) {
    $theme = Theme::factory()->create(['folder_name' => $themeFolder]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'plan' => 'pro',
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);
    seedDeliveryCheckout($restaurant);

    $this->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('checkoutPricing', false)
        ->assertSee('checkout-map', false)
        ->assertSee('data-restaurant-latitude="33.5138"', false)
        ->assertSee('data-restaurant-longitude="36.2765"', false)
        ->assertSee("bindPopup('موقع المطعم')", false)
        ->assertSee("bindPopup('موقع التوصيل الخاص بك')", false)
        ->assertSee('L.circle(defaultLocation', false)
        ->assertSee('name="name"', false)
        ->assertSee('id="use-current-location"', false)
        ->assertSee('x-text="formatDistance() || \'حدد موقع التوصيل\'"', false)
        ->assertSee('>حدد موقع التوصيل</span>', false)
        ->assertDontSee('جاري التحديد...', false)
        ->assertSee('x-text="deliveryFeeDisplay"', false)
        ->assertSee('x-text="deliveryFeeHelper"', false)
        ->assertSee('x-show="extraKilometers > 0"', false)
        ->assertDontSee('x-show="deliveryFee > 0"', false)
        ->assertSee('x-text="totalDisplay"', false)
        ->assertSee('<template x-if="warningMessage.length > 0">', false)
        ->assertSee(':disabled="processing || isOutOfRange"', false)
        ->assertSee('x-model="customerLat" @input="persistCustomer(); recalculate()"', false)
        ->assertSee('x-model="customerLng" @input="persistCustomer(); recalculate()"', false)
        ->assertSee('navigator.geolocation.getCurrentPosition', false)
        ->assertSee('maxZoom: 19', false)
        ->assertDontSee('fitBounds(', false);

    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'pending',
        'customer_lat' => 33.52,
        'customer_lng' => 36.28,
        'distance_km' => 1.25,
    ]);
    $this->actingAs($user)
        ->get(route('kitchen.display', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('رفض الطلب')
        ->assertSee('عرض الموقع');

    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'cancelled',
        'rejection_reason' => 'العنوان غير واضح',
    ]);

    $this->get(route('order.track', [$restaurant->slug, $order->tracking_code]))
        ->assertSuccessful()
        ->assertSee('تعذر قبول طلبك. السبب: العنوان غير واضح');
})->with(deliveryCheckoutThemeFolders());

it('updates Alpine customer coordinate state before recalculating checkout pricing', function () {
    $pricingScript = file_get_contents(resource_path('js/checkout-pricing.js'));

    expect($pricingScript)
        ->toContain('window.checkoutPricingState = this;')
        ->toContain("window.addEventListener('customer-location-updated', (event) => {")
        ->toContain('this.setCustomerLocation(latitude, longitude);')
        ->toContain('setCustomerLocation(latitude, longitude)')
        ->toContain('this.customerLat = String(latitude)')
        ->toContain('this.customerLng = String(longitude)')
        ->toContain('this.recalculate();')
        ->toContain('this.distance = 6371 * 2 * Math.atan2(')
        ->toContain('return this.distance === null ? \'\' : `${this.distance.toFixed(2)} كم`;')
        ->toContain('this.deliveryFee = Math.round((')
        ->toContain('this.isOutOfRange = true;')
        ->toContain('this.total = Number(configuration.subtotal)')
        ->toContain('this.warningMessage = `نعتذر، موقعك يبعد');
});

it('dispatches checkout map location updates for every installed theme', function (string $themeFolder) {
    $theme = Theme::factory()->create(['folder_name' => $themeFolder]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ]);
    seedDeliveryCheckout($restaurant);

    $this->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('setView(defaultLocation, 16)', false)
        ->assertSee("window.dispatchEvent(new CustomEvent('customer-location-updated'", false)
        ->assertSee('detail: { latitude, longitude }', false)
        ->assertSee('window.checkoutPricingState?.setCustomerLocation(', false)
        ->assertSee("checkoutMarker.on('drag'", false)
        ->assertSee("checkoutMarker.on('dragend'", false)
        ->assertSee('updateLocationAndPricing(', false)
        ->assertSee("checkoutMap.on('click'", false)
        ->assertSee('x-cloak x-show="isOutOfRange"', false)
        ->assertSee(':disabled="processing || isOutOfRange"', false)
        ->assertSee(':class="{\'cursor-not-allowed opacity-50\': isOutOfRange}"', false)
        ->assertDontSee('fitBounds(', false);
})->with(deliveryCheckoutThemeFolders());

it('persists a KDS rejection reason and sends it with the cancellation notification', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'pending',
    ]);
    Notification::fake();

    $this->actingAs($user)
        ->postJson(route('kitchen.orders.reject', $order->tracking_code), [
            'rejection_reason' => 'خارج نطاق التوصيل',
        ])
        ->assertSuccessful()
        ->assertJsonPath('status', 'cancelled')
        ->assertJsonPath('rejection_reason', 'خارج نطاق التوصيل');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->rejection_reason)->toBe('خارج نطاق التوصيل');

    Notification::assertSentTo($order, OrderStatusUpdatedNotification::class, function (
        OrderStatusUpdatedNotification $notification,
    ) use ($order): bool {
        $payload = $notification->toBroadcast($order)->data;

        return $payload['status'] === 'cancelled'
            && $payload['rejection_reason'] === 'خارج نطاق التوصيل';
    });
});

it('allows restaurant admins to reject an order from the Filament table', function () {
    $restaurant = Restaurant::factory()->create();
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'pending',
    ]);
    Notification::fake();

    Livewire::actingAs($user)
        ->test(ListOrders::class)
        ->callTableAction('reject', $order, ['rejection_reason' => 'العنوان غير واضح'])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->rejection_reason)->toBe('العنوان غير واضح');

    Notification::assertSentTo($order, OrderStatusUpdatedNotification::class, function (
        OrderStatusUpdatedNotification $notification,
    ) use ($order): bool {
        return $notification->toBroadcast($order)->data['rejection_reason'] === 'العنوان غير واضح';
    });
});
