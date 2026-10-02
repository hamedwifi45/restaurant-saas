<?php

use App\Filament\Restaurant\Pages\RestaurantProfileSettings;
use App\Models\Restaurant;
use App\Models\User;
use Livewire\Livewire;

it('allows restaurant admins to edit lightweight profile fields only', function () {
    $restaurant = Restaurant::factory()->create([
        'slug' => 'protected-slug',
        'subdomain' => 'protected-subdomain',
        'plan' => 'starter',
        'is_active' => true,
        'phone' => '0551234567',
    ]);
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    Livewire::actingAs($user)
        ->test(RestaurantProfileSettings::class)
        ->set('data.name', 'Updated Restaurant')
        ->set('data.currency_symbol', 'ل.س')
        ->set('data.cover_image', ['restaurants/covers/updated-cover.jpg'])
        ->set('data.tax_percentage', 15.5)
        ->set('data.delivery_fee', 20)
        ->set('data.latitude', 33.5138)
        ->set('data.longitude', 36.2765)
        ->set('data.delivery_radius_km', 8)
        ->set('data.base_delivery_fee', 3.5)
        ->set('data.base_delivery_km', 2.5)
        ->set('data.per_km_delivery_fee', 0.75)
        ->set('data.whatsapp_number', '0555555555')
        ->set('data.instagram_url', 'https://instagram.com/restaurant')
        ->set('data.facebook_url', 'https://facebook.com/restaurant')
        ->set('data.slug', 'forged-slug')
        ->set('data.subdomain', 'forged-subdomain')
        ->set('data.plan', 'pro')
        ->set('data.is_active', false)
        ->set('data.id', 999)
        ->call('save')
        ->assertHasNoErrors();

    $restaurant->refresh();

    expect($restaurant->name)->toBe('Updated Restaurant')
        ->and($restaurant->currency_symbol)->toBe('ل.س')
        ->and($restaurant->cover_image)->toBe('restaurants/covers/updated-cover.jpg')
        ->and((float) $restaurant->tax_percentage)->toBe(15.5)
        ->and((float) $restaurant->delivery_fee)->toBe(20.0)
        ->and((float) $restaurant->latitude)->toBe(33.5138)
        ->and((float) $restaurant->longitude)->toBe(36.2765)
        ->and((float) $restaurant->delivery_radius_km)->toBe(8.0)
        ->and((float) $restaurant->base_delivery_fee)->toBe(3.5)
        ->and((float) $restaurant->base_delivery_km)->toBe(2.5)
        ->and((float) $restaurant->per_km_delivery_fee)->toBe(0.75)
        ->and($restaurant->whatsapp_number)->toBe('0555555555')
        ->and($restaurant->instagram_url)->toBe('https://instagram.com/restaurant')
        ->and($restaurant->facebook_url)->toBe('https://facebook.com/restaurant')
        ->and($restaurant->slug)->toBe('protected-slug')
        ->and($restaurant->subdomain)->toBe('protected-subdomain')
        ->and($restaurant->plan)->toBe('starter')
        ->and($restaurant->is_active)->toBeTrue();
});

it('does not allow suspended restaurant admins into the restaurant panel', function () {
    $restaurant = Restaurant::factory()->create(['is_active' => false]);
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($user)
        ->get('/restaurant')
        ->assertForbidden();
});

it('renders the settings page only for an assigned restaurant admin', function () {
    $restaurant = Restaurant::factory()->create();
    $user = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($user)
        ->get('/restaurant/restaurant-profile-settings')
        ->assertOk()
        ->assertSee('إعدادات المطعم والهوية')
        ->assertSee('تواصل مع الدعم الفني لتغيير الرابط الأساسي');
});
