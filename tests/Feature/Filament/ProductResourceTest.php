<?php

use App\Filament\Restaurant\Resources\Products\Pages\CreateProduct;
use App\Filament\Restaurant\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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

it('creates a product with multiple gallery images', function () {
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
            'name' => 'Gallery product',
            'slug' => 'gallery-product',
            'description' => null,
            'price' => 25,
            'old_price' => null,
            'image' => null,
            'images' => [
                [
                    'path' => [UploadedFile::fake()->image('gallery-first.jpg')],
                    'alt' => 'First gallery image',
                    'is_primary' => true,
                ],
                [
                    'path' => [UploadedFile::fake()->image('gallery-second.jpg')],
                    'alt' => 'Second gallery image',
                    'is_primary' => false,
                ],
            ],
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('slug', 'gallery-product')->firstOrFail();
    $images = $product->images()->orderBy('sort_order')->get();

    expect($images)->toHaveCount(2)
        ->and($images->first()->alt)->toBe('First gallery image')
        ->and($images->first()->is_primary)->toBeTrue()
        ->and($images->last()->alt)->toBe('Second gallery image');

    $images->each(fn (Image $image) => Storage::disk('public')->assertExists($image->path));
});

it('only exposes a strict local image upload field for products', function () {
    $schema = ProductResource::form(Schema::make());
    $components = collect($schema->getComponents())->keyBy(fn ($component) => $component->getName());
    $field = $components->get('image');
    $gallery = $components->get('images');

    expect($components->keys())->not->toContain('image_url')
        ->and($field)->toBeInstanceOf(FileUpload::class)
        ->and($field->getLabel())->toBe('صورة المنتج')
        ->and($field->getDiskName())->toBe('public')
        ->and($field->getDirectory())->toBe('products')
        ->and($field->getMaxSize())->toBe(5120)
        ->and($field->getAcceptedFileTypes())->toBe(['image/jpeg', 'image/png', 'image/webp'])
        ->and($field->shouldPreventFilePathTampering())->toBeTrue()
        ->and($gallery)->toBeInstanceOf(Repeater::class)
        ->and($gallery->getRelationshipName())->toBe('images')
        ->and($gallery->isReorderable())->toBeTrue();
});
