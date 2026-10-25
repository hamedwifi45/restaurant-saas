<?php

namespace App\Models;

use Database\Factories\Order_itemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order_item extends Model
{
    /** @use HasFactory<Order_itemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'variant_name',
        'notes',
        'total',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
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

    public function getQtyAttribute(): int
    {
        return $this->attributes['quantity'] ?? 1;
    }
}
