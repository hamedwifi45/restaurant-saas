<?php

namespace Tests\Feature\Theme;

use App\Models\Restaurant;
use App\Models\RestaurantThemeSetting;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class AdvancedThemeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // إنشاء ثيم أساسي للاختبار
        $this->createTestTheme('test-theme', 'Test Theme');
    }

    public function test_theme_can_be_activated(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        
        $response = $this->postJson(route('admin.themes.activate', $theme));
        
        $response->assertOk();
        $this->assertTrue($theme->fresh()->is_active);
    }

    public function test_theme_can_be_deactivated(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        $theme->update(['is_active' => true]);
        
        $response = $this->postJson(route('admin.themes.deactivate', $theme));
        
        $response->assertOk();
        $this->assertFalse($theme->fresh()->is_active);
    }

    public function test_theme_can_be_cloned(): void
    {
        $originalTheme = Theme::where('slug', 'test-theme')->first();
        
        $response = $this->postJson(route('admin.themes.clone', $originalTheme), [
            'new_name' => 'Cloned Theme',
            'new_slug' => 'cloned-theme',
        ]);
        
        $response->assertOk();
        
        $clonedTheme = Theme::where('slug', 'cloned-theme')->first();
        $this->assertNotNull($clonedTheme);
        $this->assertEquals('Cloned Theme', $clonedTheme->name);
        $this->assertNotEquals($originalTheme->id, $clonedTheme->id);
        
        // تنظيف
        $clonedTheme->delete();
        File::deleteDirectory(resource_path('views/themes/cloned-theme'));
    }

    public function test_theme_cannot_be_cloned_with_existing_slug(): void
    {
        $originalTheme = Theme::where('slug', 'test-theme')->first();
        
        $response = $this->postJson(route('admin.themes.clone', $originalTheme), [
            'new_name' => 'Duplicate Theme',
            'new_slug' => 'test-theme', // نفس الـ slug
        ]);
        
        $response->assertStatus(422);
    }

    public function test_theme_settings_can_be_reset(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        
        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
            'subdomain' => 'test',
            'theme_id' => $theme->id,
            'is_active' => true,
        ]);
        
        // إنشاء إعدادات مخصصة
        RestaurantThemeSetting::create([
            'restaurant_id' => $restaurant->id,
            'theme_id' => $theme->id,
            'settings' => [
                'primary_color' => '#CUSTOM',
                'font_family' => 'CustomFont',
            ],
        ]);
        
        $response = $this->postJson(route('admin.themes.reset-settings', $theme));
        
        $response->assertOk();
        
        // يجب حذف الإعدادات المخصصة
        $this->assertDatabaseMissing('restaurant_theme_settings', [
            'theme_id' => $theme->id,
        ]);
    }

    public function test_theme_cannot_be_deleted_if_in_use(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        
        Restaurant::create([
            'name' => 'Using Restaurant',
            'slug' => 'using-restaurant',
            'subdomain' => 'using',
            'theme_id' => $theme->id,
            'is_active' => true,
        ]);
        
        $response = $this->deleteJson(route('admin.themes.destroy', $theme));
        
        $response->assertStatus(403);
        $this->assertDatabaseHas('themes', ['id' => $theme->id]);
    }

    public function test_theme_can_be_deleted_if_not_in_use(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        $themeFolder = resource_path('views/themes/'.$theme->folder_name);
        
        // التأكد من عدم استخدام الثيم
        Restaurant::where('theme_id', $theme->id)->update(['theme_id' => null]);
        
        $response = $this->deleteJson(route('admin.themes.destroy', $theme));
        
        $response->assertOk();
        $this->assertDatabaseMissing('themes', ['id' => $theme->id]);
        // ملاحظة: قد لا نحذف المجلد فعلياً في الاختبار
    }

    public function test_theme_preview_page_loads(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        
        $response = $this->get(route('admin.themes.preview', $theme));
        
        $response->assertOk();
        $response->assertViewIs('filament.pages.theme-preview');
        $response->assertViewHas('theme', $theme);
    }

    public function test_restaurant_preview_with_theme_parameter(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        
        $restaurant = Restaurant::create([
            'name' => 'Preview Restaurant',
            'slug' => 'preview-restaurant',
            'subdomain' => 'preview',
            'theme_id' => $theme->id,
            'is_active' => true,
        ]);
        
        $response = $this->get(route('restaurant.home', ['preview_theme' => $theme->slug]));
        
        // يجب أن تنجح المعاينة حتى لو لم يكن الثيم مفعل
        $response->assertStatus(200);
    }

    public function test_activate_theme_from_preview(): void
    {
        $theme = Theme::where('slug', 'test-theme')->first();
        
        $restaurant = Restaurant::create([
            'name' => 'Activate Test Restaurant',
            'slug' => 'activate-test',
            'subdomain' => 'activatetest',
            'theme_id' => null,
            'is_active' => true,
        ]);
        
        $user = User::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);
        
        $this->actingAs($user);
        
        $response = $this->postJson(route('admin.themes.activate', $theme), [
            'restaurant_id' => $restaurant->id,
        ]);
        
        $response->assertOk();
        $this->assertEquals($theme->id, $restaurant->fresh()->theme_id);
    }

    protected function createTestTheme(string $slug, string $name): void
    {
        $themePath = resource_path('views/themes/'.$slug);
        
        if (!File::exists($themePath)) {
            File::makeDirectory($themePath, 0755, true);
        }
        
        // إنشاء theme.json
        File::put($themePath.'/theme.json', json_encode([
            'name' => $name,
            'slug' => $slug,
            'author' => 'Test Author',
            'version' => '1.0.0',
            'description' => 'Test theme for automated testing',
            'default_settings' => [
                'primary_color' => '#FF6B35',
                'secondary_color' => '#FFFFFF',
            ],
            'allowed_variables' => [
                'primary_color' => [
                    'type' => 'color',
                    'label' => 'اللون الرئيسي',
                    'default' => '#FF6B35',
                ],
                'secondary_color' => [
                    'type' => 'color',
                    'label' => 'اللون الثانوي',
                    'default' => '#FFFFFF',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE));
        
        // إنشاء layout.blade.php
        File::put($themePath.'/layout.blade.php', '<html><body>@yield("content")</body></html>');
        
        // إنشاء صفحات أساسية
        $pages = ['home', 'menu', 'product'];
        foreach ($pages as $page) {
            $pagePath = $themePath.'/pages';
            if (!File::exists($pagePath)) {
                File::makeDirectory($pagePath, 0755, true);
            }
            File::put($pagePath.'/'.$page.'.blade.php', '<div>'.$page.'</div>');
        }
        
        // إنشاء سجل في قاعدة البيانات
        Theme::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'folder_name' => $slug,
                'author' => 'Test Author',
                'version' => '1.0.0',
                'description' => 'Test theme for automated testing',
                'default_settings' => [
                    'primary_color' => '#FF6B35',
                    'secondary_color' => '#FFFFFF',
                ],
                'allowed_variables' => [
                    'primary_color' => [
                        'type' => 'color',
                        'label' => 'اللون الرئيسي',
                        'default' => '#FF6B35',
                    ],
                    'secondary_color' => [
                        'type' => 'color',
                        'label' => 'اللون الثانوي',
                        'default' => '#FFFFFF',
                    ],
                ],
                'is_active' => false,
            ]
        );
    }

    protected function tearDown(): void
    {
        // تنظيف الثيمات التجريبية
        $testThemes = Theme::where('slug', 'like', 'test-%')
            ->orWhere('slug', 'cloned-theme')
            ->get();
        
        foreach ($testThemes as $theme) {
            $themePath = resource_path('views/themes/'.$theme->folder_name);
            if (File::exists($themePath)) {
                File::deleteDirectory($themePath);
            }
            $theme->delete();
        }
        
        parent::tearDown();
    }
}
