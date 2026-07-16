<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    protected $fillable = [
        'restaurant_id',
        'title',
        'description',
        'image',
        'is_active',
        'type',
        'value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'starts_at',
        'ends_at',
        'apply_to_all',
        'product_ids',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'apply_to_all' => 'boolean',
        'product_ids' => 'array',
    ];

    // العلاقات
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function($q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            });
    }

    // Helper Methods
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) return false;
        
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        
        return true;
    }

    public function getDiscountLabel(): string
    {
        return match($this->type) {
            'percentage' => "خصم {$this->value}%",
            'fixed_amount' => "خصم {$this->value} ر.س",
            'free_product' => 'منتج مجاني',
            'free_shipping' => 'شحن مجاني',
        };
    }

    public function getDiscountAmount(float $subtotal): float
    {
        if ($subtotal < $this->min_order_amount) {
            return 0;
        }

        return match($this->type) {
            'percentage' => ($subtotal * $this->value) / 100,
            'fixed_amount' => min($this->value, $subtotal),
            'free_product' => 0, // يُحسب بشكل منفصل
            'free_shipping' => 0, // يُحسب بشكل منفصل
        };
    }

    public function appliesToProduct(int $productId): bool
    {
        if ($this->apply_to_all) {
            return true;
        }

        return in_array($productId, $this->product_ids ?? []);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'offer_product', 'offer_id', 'product_id');
    }
    public function incrementUsage(): void
    {
        $this->increment('used_count');
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
