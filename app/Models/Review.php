<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Review extends Model
{
    protected $fillable = [
        'order_id',
        'restaurant_id',
        'customer_name',
        'rating',
        'comment',
    ];


    // العلاقات
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // صور التقييم (علاقة متعددة)
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }


    // حساب متوسط التقييم للمطعم
    public static function averageRating($restaurantId)
    {
        return self::where('restaurant_id', $restaurantId)
            
            ->avg('rating');
    }
}
