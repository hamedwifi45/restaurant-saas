<?php

use App\Models\Coupon;
use App\Models\Restaurant;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('oriental offers page shows only currently active restaurant coupons', function () {
    $theme = Theme::factory()->create([
        'folder_name' => 'oriental-theme',
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
    ]);
    $activeCoupon = Coupon::factory()->create([
        'restaurant_id' => $restaurant->id,
        'code' => 'ORIENTAL20',
        'name' => 'خصم الضيافة',
    ]);
    Coupon::factory()->create([
        'restaurant_id' => $restaurant->id,
        'code' => 'EXPIRED20',
        'ends_at' => now()->subDay(),
    ]);

    $this->get(route('offers.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee($activeCoupon->code)
        ->assertDontSee('EXPIRED20');
});
