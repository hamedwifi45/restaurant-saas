<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'description',
        'logo',
        'cover_image',
        'phone',
        'email',
        'address',
        'city',
        'delivery_fee',
        'estimated_delivery_time',
        'theme_id',
        'primary_color',
        'secondary_color',
        'background_color',
        'qr_code_image',
        'bank_details',
        'pricing_type',
        'commission_rate',
        'subscription_fee',
        'is_active',
        'trial_ends_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'subscription_fee' => 'decimal:2',
        'trial_ends_at' => 'datetime',
    ];

    // Relationships
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getFullSubdomainAttribute(): string
    {
        return $this->subdomain ? "{$this->subdomain}." . config('app.domain') : $this->slug;
    }
    // العلاقة مع التقييمات
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function activeOffers(): HasMany
    {
        return $this->hasMany(Offer::class)->active();
    }

    // متوسط التقييم
    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    // عدد التقييمات
    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }
}
