<?php

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Providers\Filament\RestaurantPanelProvider;
use Filament\Panel;
use Illuminate\Container\Container;

it('registers a dedicated restaurant panel with the expected route', function () {
    $app = Container::getInstance();
    $provider = new RestaurantPanelProvider($app);

    $panel = $provider->panel(Panel::make());

    expect($panel->getId())->toBe('restaurant')
        ->and($panel->getPath())->toBe('restaurant');
});

it('loads the custom restaurant dashboard page for authenticated users', function () {
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => Restaurant::factory()->create()->id,
    ]);

    $response = $this->actingAs($user)->get('/restaurant');

    $response->assertOk()
        ->assertSee('لوحة التحكم')
        ->assertSee('إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة)')
        ->assertSee('استقبال الطلبات متاح')
        ->assertSee('لوحة التوصيل والمندوبين')
        ->assertSee(route('driver.dashboard', ['slug' => $user->restaurant->slug]), false);
});

it('renders dashboard figures from the authenticated restaurant orders', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'زبون حقيقي',
        'customer_phone' => '0999999999',
        'subtotal' => 125000,
        'total_amount' => 125000,
        'final_amount' => 125000,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get('/restaurant');

    $response->assertOk()
        ->assertSee('125.000 ل.س')
        ->assertSee('زبون حقيقي')
        ->assertSee('قيد الانتظار')
        ->assertDontSee('FinancialOverviewWidget')
        ->assertDontSee('SalesChartWidget')
        ->assertDontSee('PaymentMethodChartWidget');
});
