<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
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
