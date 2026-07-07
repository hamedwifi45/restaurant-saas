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
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * العلاقة مع المطعم
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * العلاقة مع المنتجات
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * تعيين المطعم تلقائياً عند الإنشاء
     */
    
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
