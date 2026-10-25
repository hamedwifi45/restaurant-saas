<?php

use App\Helpers\ThemeHelper;
use App\Livewire\Restaurant\PointOfSale;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\User;
use App\Services\PosOrderService;
use App\Services\ReservationAvailabilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function tenantRoutingStaff(Restaurant $restaurant, string $role = 'cashier'): User
{
    return User::factory()->create([
        'restaurant_id' => $restaurant->id,
        'role' => $role,
        'is_active' => true,
    ]);
}

function tenantRoutingOrder(Restaurant $restaurant, string $trackingCode): Order
{
    return $restaurant->orders()->create([
        'customer_name' => 'Tenant Routing Customer',
        'customer_phone' => '0912345678',
        'delivery_type' => 'takeaway',
        'subtotal' => 10,
        'total_amount' => 10,
        'final_amount' => 10,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'status' => 'pending',
        'tracking_code' => $trackingCode,
    ]);
}

test('slug-first pos route works locally and rejects invalid or inactive restaurants', function () {
    $restaurant = Restaurant::factory()->create(['slug' => 'qasr-al-sham']);
    $cashier = tenantRoutingStaff($restaurant);

    expect(route('pos.index', ['slug' => $restaurant->slug], false))
        ->toBe('/qasr-al-sham/pos');

    $this->actingAs($cashier)
        ->get('/qasr-al-sham/pos')
        ->assertSuccessful()
        ->assertViewIs('pos.index')
        ->assertSeeLivewire(PointOfSale::class)
        ->assertSee('Restaurant SaaS')
        ->assertDontSee('Laravel Starter Kit')
        ->assertDontSee('data-theme-slot="pos-presentation"', false)
        ->assertViewHas('restaurant', fn (Restaurant $resolved): bool => $resolved->is($restaurant));

    $this->get('http://valid-shop.'.config('app.domain').'/pos')->assertNotFound();

    $inactiveRestaurant = Restaurant::factory()->create(['is_active' => false]);
    $this->get('/'.$inactiveRestaurant->slug.'/pos')->assertForbidden();
    $this->get('/unknown-shop/pos')->assertForbidden();

    expect($inactiveRestaurant->fresh()->is_active)->toBeFalse();
});

test('legacy pos path redirects to the local slug-first route', function () {
    $restaurant = Restaurant::factory()->create();
    $cashier = tenantRoutingStaff($restaurant);

    $this->actingAs($cashier)
        ->get('/pos/'.$restaurant->slug)
        ->assertRedirect(route('pos.index', ['slug' => $restaurant->slug]));
});

test('pos route rejects a slug belonging to another staff restaurant', function () {
    $restaurantA = Restaurant::factory()->create();
    $restaurantB = Restaurant::factory()->create();
    $cashierA = tenantRoutingStaff($restaurantA);

    $this->actingAs($cashierA)
        ->get('/'.$restaurantA->slug.'/pos')
        ->assertSuccessful();

    $this->get('/'.$restaurantB->slug.'/pos')->assertForbidden();
});

test('slug-first KDS and its updates remain scoped to the current restaurant', function () {
    $restaurantA = Restaurant::factory()->create(['plan' => 'pro']);
    $restaurantB = Restaurant::factory()->create(['plan' => 'pro']);
    $staffA = tenantRoutingStaff($restaurantA, 'kitchen_staff');
    $orderA = tenantRoutingOrder($restaurantA, 'KDS-A-ORDER');
    $orderB = tenantRoutingOrder($restaurantB, 'KDS-B-ORDER');

    expect(route('kitchen.display', ['slug' => $restaurantA->slug], false))
        ->toBe('/'.$restaurantA->slug.'/kds');

    $this->actingAs($staffA)
        ->get('/'.$restaurantA->slug.'/kds')
        ->assertSuccessful()
        ->assertViewHas('orderCollection', fn ($orders): bool => $orders->contains('id', $orderA->id))
        ->assertSee('KDS-A-ORDER')
        ->assertDontSee('KDS-B-ORDER');

    $this->get('/'.$restaurantA->slug.'/kds/orders')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', 'KDS-A-ORDER')
        ->assertJsonMissing(['id' => 'KDS-B-ORDER']);

    $this->postJson('/'.$restaurantA->slug.'/kds/orders/'.$orderA->tracking_code.'/status', [
        'status' => 'preparing',
    ])->assertSuccessful();

    $this->postJson('/'.$restaurantA->slug.'/kds/orders/'.$orderA->tracking_code.'/status', [
        'status' => 'ready',
    ])->assertSuccessful();

    $this->getJson('/'.$restaurantA->slug.'/kds/orders')
        ->assertJsonPath('data.0.status', 'ready');

    $this->postJson('/'.$restaurantB->slug.'/kds/orders/'.$orderB->tracking_code.'/status', [
        'status' => 'preparing',
    ])->assertForbidden();

    $this->postJson('/'.$restaurantA->slug.'/kds/orders/'.$orderB->tracking_code.'/status', [
        'status' => 'preparing',
    ])->assertNotFound();

    $this->postJson('/'.$restaurantA->slug.'/kds/orders/'.$orderB->tracking_code.'/reject', [
        'rejection_reason' => 'أخرى',
    ])->assertNotFound();

    expect($orderA->fresh()->status)->toBe('ready')
        ->and($orderB->fresh()->status)->toBe('pending');
});

test('slug-first receipt route binds the tracking code to the restaurant', function () {
    $restaurantA = Restaurant::factory()->create();
    $restaurantB = Restaurant::factory()->create();
    $cashierA = tenantRoutingStaff($restaurantA);
    $cashierB = tenantRoutingStaff($restaurantB);
    $orderA = tenantRoutingOrder($restaurantA, 'RECEIPT-A');

    expect(route('pos.receipt', [
        'slug' => $restaurantA->slug,
        'trackingCode' => $orderA->tracking_code,
    ], false))->toBe('/'.$restaurantA->slug.'/pos/orders/RECEIPT-A/receipt');

    $this->actingAs($cashierA)
        ->get('/'.$restaurantA->slug.'/pos/orders/'.$orderA->tracking_code.'/receipt')
        ->assertSuccessful()
        ->assertSee('RECEIPT-A');

    $this->get('/'.$restaurantB->slug.'/pos/orders/'.$orderA->tracking_code.'/receipt')
        ->assertForbidden();

    $this->actingAs($cashierB)
        ->get('/'.$restaurantB->slug.'/pos/orders/'.$orderA->tracking_code.'/receipt')
        ->assertNotFound();
});

test('livewire actions reject a slug changed to another restaurant', function () {
    $restaurantA = Restaurant::factory()->create();
    $restaurantB = Restaurant::factory()->create();
    $cashierA = tenantRoutingStaff($restaurantA);

    $this->actingAs($cashierA);
    $component = app(PointOfSale::class);
    $component->boot(
        app(PosOrderService::class),
        app(ReservationAvailabilityService::class),
    );
    $component->mount($restaurantA->slug);
    $component->slug = $restaurantB->slug;

    expect(fn () => $component->clearCart())->toThrow(AuthorizationException::class);
});

test('theme layouts and optional KDS and receipt slots select safe fallbacks', function () {
    $themed = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $fallbackTheme = Theme::factory()->create(['folder_name' => 'albasha-burger-example']);
    $restaurant = Restaurant::factory()->create(['theme_id' => $themed->id]);
    $fallbackRestaurant = Restaurant::factory()->create(['theme_id' => $fallbackTheme->id]);

    expect(ThemeHelper::layoutView($restaurant))->toBe('themes.albasha-burger.layout')
        ->and(ThemeHelper::viewOrFallback($fallbackRestaurant, 'orders.pos-receipt', 'pos.partials.receipt-header'))
        ->toBe('pos.partials.receipt-header');

    $themeWithoutKds = Theme::factory()->create(['folder_name' => 'theme-without-kds']);
    $themeDirectory = resource_path('views/themes/theme-without-kds');
    File::ensureDirectoryExists($themeDirectory);
    $restaurantWithoutKds = Restaurant::factory()->create([
        'plan' => 'pro',
        'theme_id' => $themeWithoutKds->id,
    ]);
    $kitchenStaff = tenantRoutingStaff($restaurantWithoutKds, 'kitchen_staff');

    try {
        $this->actingAs($kitchenStaff)
            ->get('/'.$restaurantWithoutKds->slug.'/kds')
            ->assertSuccessful()
            ->assertViewIs('themes.albasha-burger.pages.kds-board');
    } finally {
        File::deleteDirectory($themeDirectory);
    }
});

test('POS uses the Flux app layout while receipts keep the selected theme header', function () {
    $theme = Theme::factory()->create(['folder_name' => 'albasha-burger']);
    $restaurant = Restaurant::factory()->create(['theme_id' => $theme->id]);
    $cashier = tenantRoutingStaff($restaurant);
    $order = tenantRoutingOrder($restaurant, 'THEMED-RECEIPT');

    $this->actingAs($cashier)
        ->get('/'.$restaurant->slug.'/pos')
        ->assertSuccessful()
        ->assertSee('Restaurant SaaS')
        ->assertDontSee('data-theme-slot="pos-presentation"', false);

    $this->get('/'.$restaurant->slug.'/pos/orders/'.$order->tracking_code.'/receipt')
        ->assertSuccessful()
        ->assertSee('data-theme-slot="receipt-header"', false)
        ->assertDontSee('data-flux', false);
});
