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

    expect(Order::query()->where('restaurant_id', $restaurant->id)->exists())->toBeFalse();
});

it('stores the calculated customer distance and distance-based delivery fee', function () {
    $restaurant = Restaurant::factory()->create([
        'latitude' => 0,
        'longitude' => 0,
        'delivery_radius_km' => 20,
        'base_delivery_fee' => 2,
        'base_delivery_km' => 2,
        'per_km_delivery_fee' => 0.5,
        'tax_percentage' => 0,
    ]);
    seedDeliveryCheckout($restaurant);

    $this->post(route('checkout.store', $restaurant->slug), deliveryCheckoutPayload(0, 0.1))
        ->assertRedirect();

    $order = Order::query()->where('restaurant_id', $restaurant->id)->firstOrFail();

    expect((float) $order->distance_km)->toBe(11.12)
        ->and((float) $order->delivery_fee)->toBe(6.56)
        ->and((float) $order->total_amount)->toBe(26.56)
        ->and((float) $order->customer_lat)->toBe(0.0)
        ->and((float) $order->customer_lng)->toBe(0.1);
});

it('renders the checkout map and rejection banner for each customer theme', function (string $themeFolder) {
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
        ->assertSee('name="name"', false);

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
})->with([
    'burger theme' => 'burger-theme',
    'classic theme' => 'classic-2-burger',
    'oriental theme' => 'oriental-theme',
]);

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
