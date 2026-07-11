<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'old_price',
        'image',
        'is_available',
        'is_featured',
        'sort_order',
    ];
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    // صور المنتج (علاقة متعددة)
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }

    // الصورة الرئيسية
    public function primaryImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_primary', true);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (auth()->check() && auth()->user()->restaurant_id) {
                $product->restaurant_id = auth()->user()->restaurant_id;
            }
        });
    }
}
