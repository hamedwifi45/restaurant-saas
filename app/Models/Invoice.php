<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'restaurant_id',
        'invoice_number',
        'subtotal',
        'tax_amount',
        'delivery_fee',
        'total_amount',
        'final_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
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
        if (auth()->check() && auth()->user()->role != 'super_admin') {
            static::creating(function ($category) {
                if (auth()->check() && auth()->user()->restaurant_id) {
                    $category->restaurant_id = auth()->user()->restaurant_id;
                }
            });
        }
    }
}
