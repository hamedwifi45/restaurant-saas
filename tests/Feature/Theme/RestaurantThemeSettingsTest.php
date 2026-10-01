<?php

namespace Tests\Feature\Theme;

use App\Models\Restaurant;
use App\Models\RestaurantThemeSetting;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_owner_can_save_only_allowed_theme_variables(): void
    {
        $theme = Theme::create([
            'name' => 'Burger Classic',
            'slug' => 'burger-classic',
            'folder_name' => 'burger-theme',
            'author' => 'Platform',
            'version' => '1.0.0',
            'default_settings' => [
                'primary_color' => '#FF6B35',
                'secondary_color' => '#FFFFFF',
                'background_color' => '#1A1A1A',
                'font_family' => 'Tajawal',
                'show_hero_banner' => true,
            ],
            'allowed_variables' => [
                'primary_color' => [
                    'type' => 'color',
                    'label' => 'اللون الرئيسي',
                    'default' => '#FF6B35',
                ],
                'font_family' => [
                    'type' => 'select',
                    'label' => 'نوع الخط',
                    'options' => [
                        'Tajawal' => 'Tajawal',
                        'Cairo' => 'Cairo',
                    ],
                    'default' => 'Tajawal',
                ],
                'show_hero_banner' => [
                    'type' => 'boolean',
                    'label' => 'إظهار البانر',
                    'default' => true,
                ],
            ],
        ]);

        $restaurant = Restaurant::create([
            'name' => 'مطعم المثال',
            'slug' => 'example-restaurant',
            'subdomain' => 'example',
            'theme_id' => $theme->id,
            'plan' => 'pro',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'restaurant_admin',
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('restaurant.theme.settings.update'), [
            'settings' => [
                'primary_color' => '#112233',
                'font_family' => 'Cairo',
                'show_hero_banner' => false,
                'not_allowed' => 'malicious-value',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('settings.primary_color', '#112233')
            ->assertJsonPath('settings.font_family', 'Cairo')
            ->assertJsonPath('settings.show_hero_banner', false)
            ->assertJsonMissingPath('settings.not_allowed');

        $this->assertDatabaseHas('restaurant_theme_settings', [
            'restaurant_id' => $restaurant->id,
            'theme_id' => $theme->id,
        ]);

        $savedSettings = RestaurantThemeSetting::first()->settings ?? [];

        $this->assertSame('#112233', $savedSettings['primary_color']);
        $this->assertSame('Cairo', $savedSettings['font_family']);
        $this->assertFalse($savedSettings['show_hero_banner']);
        $this->assertArrayNotHasKey('not_allowed', $savedSettings);
    }
}
