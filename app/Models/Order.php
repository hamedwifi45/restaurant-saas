<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'coupon_id',
        'coupon_code',
        'coupon_discount',
        'delivery_type',
        'delivery_address',
        'final_amount',   
        'payment_method',
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
        'offer_id',
        'discount_amount',
    ];
    // العلاقة مع التقييم
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
    // هل تم تقييم هذا الطلب؟
    public function getIsReviewedAttribute(): bool
    {
        return $this->review()->exists();
    }
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
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
