<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_type',
        'delivery_address',
        'delivery_city',
        'delivery_fee',
        'subtotal',
        'discount',
        'total_amount',
        'payment_status',
        'payment_receipt',
        'status',
        'notes',
        'rejection_reason',
        'tracking_code',
        'rating',
        'review',
    ];
    // العلاقة مع التقييم
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // هل تم تقييم هذا الطلب؟
    public function getIsReviewedAttribute(): bool
    {
        return $this->review()->exists();
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (auth()->check() && auth()->user()->restaurant_id) {
                $order->restaurant_id = auth()->user()->restaurant_id;
            }
        });
    }
    public function items(): HasMany
    {
    return $this->hasMany(Order_item::class);
    }
}
