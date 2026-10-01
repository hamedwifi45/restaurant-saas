<?php

use App\Filament\Restaurant\Pages\StopImpersonation;
use App\Filament\Sham\Resources\Restaurants\Actions\ImpersonateRestaurantAdmin;
use App\Filament\Sham\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Sham\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Sham\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Sham\Widgets\SystemOverviewWidget;
use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('creates a restaurant owner account from the super admin restaurant form', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $theme = Theme::factory()->create();

    Livewire::actingAs($superAdmin)
        ->test(CreateRestaurant::class)
        ->fillForm([
            'name' => 'Owner Managed Restaurant',
            'slug' => 'owner-managed-restaurant',
            'plan' => 'pro',
            'theme_id' => $theme->id,
            'owner_name' => 'Restaurant Owner',
            'owner_email' => 'owner@example.test',
            'owner_password' => 'StrongPassword123!',
            'owner_phone' => '0551234567',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $restaurant = Restaurant::query()->where('slug', 'owner-managed-restaurant')->firstOrFail();
    $owner = $restaurant->restaurantAdmin;

    expect($restaurant->plan)->toBe('pro')
        ->and($owner)->not->toBeNull()
        ->and($owner->name)->toBe('Restaurant Owner')
        ->and($owner->email)->toBe('owner@example.test')
        ->and($owner->restaurant_id)->toBe($restaurant->id)
        ->and(Hash::check('StrongPassword123!', $owner->password))->toBeTrue();
});

it('returns to the super admin account after impersonation', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $restaurant = Restaurant::factory()->create();
    $restaurantAdmin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    $session = app('session.store');
    $session->start();
    $session->put('super_admin_impersonator_id', $superAdmin->id);
    request()->setLaravelSession($session);
    auth()->login($restaurantAdmin);

    $response = (new StopImpersonation)->stopImpersonating();

    expect(parse_url($response->getTargetUrl(), PHP_URL_PATH))->toBe('/sham');
    $this->assertAuthenticatedAs($superAdmin);
});

it('updates the related restaurant owner from the edit form', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $restaurant = Restaurant::factory()->create(['phone' => '0551234567']);
    $owner = User::factory()->create([
        'name' => 'Old Owner',
        'email' => 'old-owner@example.test',
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    Livewire::actingAs($superAdmin)
        ->test(EditRestaurant::class, ['record' => $restaurant->getRouteKey()])
        ->assertFormSet([
            'owner_name' => 'Old Owner',
            'owner_email' => 'old-owner@example.test',
        ])
        ->fillForm([
            'owner_name' => 'Updated Owner',
            'owner_email' => 'updated-owner@example.test',
            'owner_phone' => '0551234567',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $owner->refresh();

    expect($owner->name)->toBe('Updated Owner')
        ->and($owner->email)->toBe('updated-owner@example.test')
        ->and($owner->restaurant_id)->toBe($restaurant->id);
});

it('supports changing the plan and toggling restaurant status from the table', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $restaurant = Restaurant::factory()->create([
        'plan' => 'starter',
        'is_active' => true,
    ]);

    Livewire::actingAs($superAdmin)
        ->test(ListRestaurants::class)
        ->callTableAction('changePlan', $restaurant, ['plan' => 'pro'])
        ->assertHasNoTableActionErrors();

    $restaurant->refresh();
    expect($restaurant->plan)->toBe('pro');

    Livewire::actingAs($superAdmin)
        ->test(ListRestaurants::class)
        ->callTableAction('toggleStatus', $restaurant);

    expect($restaurant->fresh()->is_active)->toBeFalse();
});

it('starts an impersonation session from the restaurant table', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $restaurant = Restaurant::factory()->create();
    $owner = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    $session = app('session.store');
    $session->start();
    request()->setLaravelSession($session);
    auth()->login($superAdmin);

    $response = app(ImpersonateRestaurantAdmin::class)->handle($restaurant);

    expect(parse_url($response->getTargetUrl(), PHP_URL_PATH))->toBe('/restaurant');
    $this->assertAuthenticatedAs($owner);
    expect(session('super_admin_impersonator_id'))->toBe($superAdmin->id);
});

it('shows the system overview to super admins', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    Restaurant::factory()->create(['plan' => 'starter']);
    Restaurant::factory()->create(['plan' => 'pro', 'is_active' => false]);

    Livewire::actingAs($superAdmin)
        ->test(SystemOverviewWidget::class)
        ->assertSee('إجمالي المطاعم')
        ->assertSee('Starter: 1 | Pro: 1')
        ->assertSee('الحسابات الموقوفة')
        ->assertSee('إيرادات الاشتراكات الشهرية المقدرة');
});
