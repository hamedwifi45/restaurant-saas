<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'old_price',
        'price_type',
        'min_price',
        'max_price',
        'variants',
        'image',
        'is_available',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'variants' => 'array',
        'price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Restaurant, $this>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // صور المنتج (علاقة متعددة)
    /**
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }

    // الصورة الرئيسية
    /**
     * @return MorphOne<Image, $this>
     */
    public function primaryImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_primary', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function (Product $product): void {
            foreach (array_unique(array_filter([
                (int) $product->getOriginal('restaurant_id'),
                (int) $product->restaurant_id,
            ])) as $restaurantId) {
                Cache::forget("restaurant_menu_{$restaurantId}");
            }
        });

        static::deleted(function (Product $product): void {
            Cache::forget("restaurant_menu_{$product->restaurant_id}");
        });

        static::creating(function ($product) {
            if (auth()->check() && auth()->user()->restaurant_id) {
                $product->restaurant_id = auth()->user()->restaurant_id;
            }
        });
    }
}
