<?php

use App\Filament\Restaurant\Resources\Products\Pages\CreateProduct;
use App\Filament\Restaurant\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('creates a product from a locally uploaded image', function () {
    Storage::fake('public');

    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    Filament::setCurrentPanel('restaurant');

    Livewire::actingAs($admin)
        ->test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name' => 'Local image product',
            'slug' => 'local-image-product',
            'description' => null,
            'price' => 25,
            'old_price' => null,
            'image' => UploadedFile::fake()->image('product.jpg'),
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('slug', 'local-image-product')->firstOrFail();

    expect($product->image)
        ->toStartWith('products/')
        ->toEndWith('.jpg')
        ->not->toContain('http://')
        ->not->toContain('https://');

    Storage::disk('public')->assertExists($product->image);
});

it('only exposes a strict local image upload field for products', function () {
    $schema = ProductResource::form(Schema::make());
    $components = collect($schema->getComponents())->keyBy(fn ($component) => $component->getName());
    $field = $components->get('image');

    expect($components->keys())->not->toContain('image_url')
        ->and($field)->toBeInstanceOf(FileUpload::class)
        ->and($field->getLabel())->toBe('صورة المنتج')
        ->and($field->getDiskName())->toBe('public')
        ->and($field->getDirectory())->toBe('products')
        ->and($field->getMaxSize())->toBe(5120)
        ->and($field->getAcceptedFileTypes())->toBe(['image/jpeg', 'image/png', 'image/webp'])
        ->and($field->shouldPreventFilePathTampering())->toBeTrue();
});
