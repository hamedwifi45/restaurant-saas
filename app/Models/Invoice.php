<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'restaurant_id',
        'invoice_number',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
    protected static function boot()
    {
        parent::boot();
        if(auth()->check() && auth()->user()->role != 'super_admin' ) {
        static::creating(function ($category) {
            if (auth()->check() && auth()->user()->restaurant_id) {
                $category->restaurant_id = auth()->user()->restaurant_id;
            }
        });
        }
    }
}
