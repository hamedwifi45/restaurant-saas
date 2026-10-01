<?php

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('lists users across restaurants in the super admin resource', function () {
    Role::create(['name' => 'restaurant_admin', 'guard_name' => 'web']);

    $restaurant = Restaurant::factory()->create(['name' => 'Tenant Restaurant']);
    $superAdmin = User::factory()->create([
        'name' => 'Platform Admin',
        'email' => 'platform@example.test',
        'role' => 'super_admin',
        'restaurant_id' => null,
    ]);
    $restaurantAdmin = User::factory()->create([
        'name' => 'Tenant Admin',
        'email' => 'tenant@example.test',
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $restaurantAdmin->syncRoles(['restaurant_admin']);

    expect(UserResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($superAdmin->id, $restaurantAdmin->id);

    $this->actingAs($superAdmin)
        ->get('/sham/users')
        ->assertOk()
        ->assertSee('platform@example.test')
        ->assertSee('tenant@example.test')
        ->assertSee('Tenant Restaurant');
});

it('creates a user and synchronizes its Spatie and legacy roles', function () {
    Role::create(['name' => 'driver', 'guard_name' => 'web']);

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $restaurant = Restaurant::factory()->create();

    Livewire::actingAs($superAdmin)
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'New Driver',
            'email' => 'new-driver@example.test',
            'password' => 'DriverPassword123!',
            'roles' => 'driver',
            'restaurant_id' => $restaurant->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'new-driver@example.test')->firstOrFail();

    expect($user->role)->toBe('driver')
        ->and($user->hasRole('driver'))->toBeTrue()
        ->and($user->restaurant_id)->toBe($restaurant->id)
        ->and(Hash::check('DriverPassword123!', $user->password))->toBeTrue();
});

it('updates a user role without replacing an omitted password', function () {
    Role::create(['name' => 'restaurant_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'kitchen_staff', 'guard_name' => 'web']);

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create([
        'email' => 'existing-user@example.test',
        'role' => 'restaurant_admin',
    ]);
    $user->syncRoles(['restaurant_admin']);
    $passwordHash = $user->password;

    Livewire::actingAs($superAdmin)
        ->test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'password' => null,
            'roles' => 'kitchen_staff',
            'restaurant_id' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->role)->toBe('kitchen_staff')
        ->and($user->hasRole('kitchen_staff'))->toBeTrue()
        ->and($user->password)->toBe($passwordHash);
});
