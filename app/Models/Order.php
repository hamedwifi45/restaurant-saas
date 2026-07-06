<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    protected static function boot()
{
    parent::boot();

    static::creating(function ($order) {
        if (auth()->check() && auth()->user()->restaurant_id) {
            $order->restaurant_id = auth()->user()->restaurant_id;
        }
    });
}
}
