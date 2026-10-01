<?php

namespace Tests\Feature\Theme;

use App\Models\Theme;
use App\Services\ThemeUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ThemeUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_and_installs_a_valid_theme_zip(): void
    {
        $zipPath = $this->createArchive([
            'theme.json' => json_encode([
                'name' => 'Theme Upload Demo',
                'slug' => 'theme-upload-demo',
                'author' => 'QA',
                'version' => '1.0.0',
                'description' => 'Demo theme',
                'default_settings' => [
                    'primary_color' => '#FF6B35',
                ],
                'allowed_variables' => [
                    'primary_color' => [
                        'type' => 'color',
                        'label' => 'اللون الرئيسي',
                        'default' => '#FF6B35',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),
            'layout.blade.php' => '<html></html>',
            'pages/home.blade.php' => '<div>Home</div>',
            'pages/menu.blade.php' => '<div>Menu</div>',
            'pages/product.blade.php' => '<div>Product</div>',
            'pages/offers.blade.php' => '<div>Offers</div>',
            'pages/kds-board.blade.php' => '<div>KDS board</div>',
            'cart/cart.blade.php' => '<div>Cart</div>',
            'cart/checkout.blade.php' => '<div>Checkout</div>',
            'cart/success.blade.php' => '<div>Success</div>',
            'orders/track.blade.php' => '<div>Track</div>',
            'orders/track-form.blade.php' => '<div>Track Form</div>',
            'orders/invoice.blade.php' => '<div>Invoice</div>',
            'reviews/reviews.blade.php' => '<div>Reviews</div>',
            'reviews/review-form.blade.php' => '<div>Review Form</div>',
            'driver/dashboard.blade.php' => '<div>Driver dashboard</div>',
            'driver/orders.blade.php' => '<div>Driver orders</div>',
            'driver/tracking.blade.php' => '<div>Driver tracking</div>',
        ]);

        $result = app(ThemeUploadService::class)->upload($zipPath, 'theme-upload-demo');

        $this->assertTrue($result['success'], $result['message']);
        $this->assertFileExists(resource_path('views/themes/theme-upload-demo/theme.json'));
        $this->assertDatabaseHas('themes', [
            'slug' => 'theme-upload-demo',
            'folder_name' => 'theme-upload-demo',
        ]);

        File::deleteDirectory(resource_path('views/themes/theme-upload-demo'));
        Theme::where('slug', 'theme-upload-demo')->delete();
        @unlink($zipPath);
    }

    public function test_it_rejects_a_theme_zip_missing_required_files(): void
    {
        $zipPath = $this->createArchive([
            'theme.json' => json_encode([
                'name' => 'Broken Theme',
                'slug' => 'broken-theme',
                'version' => '1.0.0',
                'default_settings' => [],
                'allowed_variables' => [],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $result = app(ThemeUploadService::class)->upload($zipPath);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('الملفات التالية مفقودة', $result['message']);

        @unlink($zipPath);
    }

    protected function createArchive(array $files): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'theme_upload_') ?: sys_get_temp_dir().'/theme_upload_'.uniqid();
        $zipPath .= '.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $relativePath => $contents) {
            $zip->addFromString($relativePath, $contents);
        }

        $zip->close();

        return $zipPath;
    }
}
