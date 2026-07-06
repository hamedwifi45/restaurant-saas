<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        'is_active',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (auth()->check() && auth()->user()->restaurant_id) {
                $category->restaurant_id = auth()->user()->restaurant_id;
            }
        });
    }
}
