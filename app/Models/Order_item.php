<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order_item extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'variant_name',
        'total',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed(); // جلب المنتج حتى لو كان محذوفاً
    }

    protected $appends = ['name', 'qty'];

    public function getNameAttribute(): string
    {
        $productName = $this->attributes['product_name'] ?? '';
        $variantName = $this->attributes['variant_name'] ?? null;

        return filled($variantName) ? $productName.' ('.$variantName.')' : $productName;
    }

    public function getQtyAttribute()
    {
        return $this->attributes['quantity'] ?? 1;
    }
}
