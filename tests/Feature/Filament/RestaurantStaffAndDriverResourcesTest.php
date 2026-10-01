<?php

use App\Filament\Restaurant\Resources\Drivers\DriverResource;
use App\Filament\Restaurant\Resources\Drivers\Pages\CreateDriver;
use App\Filament\Restaurant\Resources\Drivers\Pages\ListDrivers;
use App\Filament\Restaurant\Resources\Staff\Pages\CreateStaff;
use App\Filament\Restaurant\Resources\Staff\StaffResource;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('scopes staff and fleet records and completed-order totals to the current restaurant', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();

    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $staff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $restaurant->id,
    ]);
    $driverUser = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $otherDriver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $otherRestaurant->id,
    ]);
    $otherStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $otherRestaurant->id,
    ]);

    Driver::query()->create([
        'user_id' => $driverUser->id,
        'vehicle_type' => 'motorcycle',
        'vehicle_model' => 'سوزوكي حمراء',
        'plate_number' => '12345',
        'is_available' => true,
    ]);
    Driver::query()->create([
        'user_id' => $otherDriver->id,
        'vehicle_type' => 'car',
        'plate_number' => '67890',
    ]);

    Order::query()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driverUser->id,
        'customer_name' => 'Customer A',
        'customer_phone' => '0930123456',
        'subtotal' => 10,
        'total_amount' => 10,
        'status' => 'delivered',
    ]);
    Order::query()->create([
        'restaurant_id' => $otherRestaurant->id,
        'driver_id' => $driverUser->id,
        'customer_name' => 'Customer B',
        'customer_phone' => '0930123457',
        'subtotal' => 10,
        'total_amount' => 10,
        'status' => 'delivered',
    ]);
    Order::query()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driverUser->id,
        'customer_name' => 'Customer C',
        'customer_phone' => '0930123458',
        'subtotal' => 10,
        'total_amount' => 10,
        'status' => 'on_way',
    ]);

    $this->actingAs($admin);

    expect(StaffResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($admin->id, $staff->id)
        ->not->toContain($otherStaff->id);

    $driverRecords = DriverResource::getEloquentQuery()->get();

    expect($driverRecords->modelKeys())
        ->toContain($driverUser->id)
        ->not->toContain($otherDriver->id)
        ->and($driverRecords->sole()->completed_orders_count)->toBe(1)
        ->and($driverRecords->sole()->driverProfile->vehicle_model)->toBe('سوزوكي حمراء');
});

it('creates staff in the authenticated restaurant with an encrypted password', function () {
    $restaurant = Restaurant::factory()->create();
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    Filament::setCurrentPanel('restaurant');

    Livewire::actingAs($admin)
        ->test(CreateStaff::class)
        ->fillForm([
            'name' => 'Kitchen Staff',
            'email' => 'kitchen@example.test',
            'phone' => '0930123456',
            'password' => 'KitchenPassword123!',
            'role' => 'kitchen_staff',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $staff = User::query()->where('email', 'kitchen@example.test')->firstOrFail();

    expect($staff->restaurant_id)->toBe($restaurant->id)
        ->and($staff->role)->toBe('kitchen_staff')
        ->and(Hash::check('KitchenPassword123!', $staff->password))->toBeTrue();
});

it('creates a driver account and its vehicle profile for the current restaurant', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    Filament::setCurrentPanel('restaurant');

    Livewire::actingAs($admin)
        ->test(CreateDriver::class)
        ->assertSee('دراجة نارية (موتور)')
        ->fillForm([
            'name' => 'Fleet Driver',
            'email' => 'fleet-driver@example.test',
            'phone' => '0930123456',
            'password' => 'DriverPassword123!',
            'vehicle_type' => 'motorcycle',
            'plate_number' => '12345',
            'vehicle_model' => 'سوزوكي حمراء',
            'is_available' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $driverUser = User::query()->where('email', 'fleet-driver@example.test')->firstOrFail();
    $profile = $driverUser->driverProfile()->firstOrFail();

    expect($driverUser->restaurant_id)->toBe($restaurant->id)
        ->and($driverUser->role)->toBe('driver')
        ->and(Hash::check('DriverPassword123!', $driverUser->password))->toBeTrue()
        ->and($profile->vehicle_type)->toBe('motorcycle')
        ->and($profile->vehicle_model)->toBe('سوزوكي حمراء')
        ->and($profile->plate_number)->toBe('12345')
        ->and($profile->is_available)->toBeTrue();
});

it('has the fleet defaults and model casts configured', function () {
    $driver = new Driver;

    expect($driver->vehicle_type)->toBe('motorcycle')
        ->and($driver->rating)->toBe(5.0)
        ->and($driver->is_available)->toBeTrue()
        ->and($driver->getCasts()['rating'])->toBe('float')
        ->and($driver->getCasts()['is_available'])->toBe('boolean');
});

it('renders canonical and Arabic vehicle type values without the unknown fallback', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    foreach ([
        'motorcycle',
        'دراجة نارية',
        'دراجة نارية (موتور)',
        'car',
        'سيارة',
        'سيارة توصيل',
        'bicycle',
        'دراجة',
        'دراجة هوائية',
    ] as $vehicleType) {
        $driver = User::factory()->create([
            'role' => 'driver',
            'restaurant_id' => $restaurant->id,
        ]);

        Driver::query()->create([
            'user_id' => $driver->id,
            'vehicle_type' => $vehicleType,
        ]);
    }

    Filament::setCurrentPanel('restaurant');

    Livewire::actingAs($admin)
        ->test(ListDrivers::class)
        ->assertSee('دراجة نارية')
        ->assertSee('سيارة توصيل')
        ->assertSee('دراجة هوائية')
        ->assertDontSee('غير محدد');
});

it('only links existing driver accounts from the authenticated restaurant', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $otherRestaurant = Restaurant::factory()->create();
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $otherDriver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $otherRestaurant->id,
    ]);

    Filament::setCurrentPanel('restaurant');

    Livewire::actingAs($admin)
        ->test(CreateDriver::class)
        ->fillForm([
            'existing_user_id' => $otherDriver->id,
            'vehicle_type' => 'car',
            'plate_number' => 'FOREIGN-1',
            'is_available' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['existing_user_id']);

    expect($otherDriver->driverProfile()->exists())->toBeFalse();
});

it('links an existing driver account without creating a duplicate user', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
        'phone' => '0930123456',
    ]);

    Filament::setCurrentPanel('restaurant');

    Livewire::actingAs($admin)
        ->test(CreateDriver::class)
        ->fillForm([
            'existing_user_id' => $driver->id,
            'vehicle_type' => 'bicycle',
            'plate_number' => 'BIKE-1',
            'is_available' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('restaurant_id', $restaurant->id)->where('role', 'driver')->count())->toBe(1)
        ->and($driver->refresh()->driverProfile->vehicle_type)->toBe('bicycle')
        ->and($driver->driverProfile->is_available)->toBeFalse()
        ->and($driver->driverProfile->phone)->toBe('0930123456');
});
