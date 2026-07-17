<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = [
        'restaurant_id',
        'code',
        'name',
        'description',
        'is_active',
        'type',
        'value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'max_uses_per_user',
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

    // Scope: كوبونات نشطة حالياً
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

    // التحقق من الصلاحية
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    // التحقق من صلاحية الكود
    public function isValidFor(float $subtotal, ?string $customerPhone = null): array
    {
        if (!$this->isCurrentlyActive()) {
            return ['valid' => false, 'message' => 'الكوبون غير صالح أو منتهي الصلاحية'];
        }

        if ($subtotal < $this->min_order_amount) {
            return [
                'valid' => false,
                'message' => 'الحد الأدنى للطلب هو ' . number_format($this->min_order_amount, 2) . ' ر.س'
            ];
        }

        // التحقق من الحد الأقصى لكل زبون
        if ($this->max_uses_per_user && $customerPhone) {
            $userUsageCount = \App\Models\Order::where('customer_phone', $customerPhone)
                ->where('coupon_id', $this->id)
                ->count();
            
            if ($userUsageCount >= $this->max_uses_per_user) {
                return ['valid' => false, 'message' => 'لقد استخدمت هذا الكوبون الحد الأقصى من المرات'];
            }
        }

        return ['valid' => true, 'message' => 'الكوبون صالح'];
    }

    // حساب قيمة الخصم
    public function getDiscountAmount(float $subtotal): float
    {
        if ($subtotal < $this->min_order_amount) {
            return 0;
        }

        return match($this->type) {
            'percentage' => ($subtotal * $this->value) / 100,
            'fixed_amount' => min($this->value, $subtotal),
        };
    }

    // التحقق من انطباق الكوبون على منتج معين
    public function appliesToProduct(int $productId): bool
    {
        if ($this->apply_to_all) return true;
        return in_array($productId, $this->product_ids ?? []);
    }

    // زيادة عداد الاستخدام
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    // عرض قيمة الخصم بشكل نصي
    public function getDiscountLabel(): string
    {
        return match($this->type) {
            'percentage' => "خصم {$this->value}%",
            'fixed_amount' => "خصم {$this->value} ر.س",
        };
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
