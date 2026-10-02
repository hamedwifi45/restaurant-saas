<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'delivery_radius_km' => 10.00,
        'base_delivery_fee' => 2.00,
        'base_delivery_km' => 2.00,
        'per_km_delivery_fee' => 0.50,
    ];

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
        'currency_symbol',
        'tax_percentage',
        'whatsapp_number',
        'instagram_url',
        'facebook_url',
        'plan',
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
        'trial_ends_at',
        'latitude',
        'longitude',
        'delivery_radius_km',
        'base_delivery_fee',
        'base_delivery_km',
        'per_km_delivery_fee',
    ];

    public const PLAN_FEATURES = [
        'starter' => [
            'qr_menu',
            'table_qr',
            'cash_payment',
            'bank_transfer',
            'basic_sales_report',
        ],
        'pro' => [
            'qr_menu',
            'table_qr',
            'cash_payment',
            'bank_transfer',
            'basic_sales_report',
            'advanced_financial_analytics', // التحليلات المالية المتقدمة
            'theme_editor',                // محرر الثيمات
            'kds',                         // شاشة المطبخ KDS
            'gps_tracking',                // تتبع المندوب اللحظي
            'pwa',                         // تطبيق PWA
        ],
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'subscription_fee' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'delivery_radius_km' => 'decimal:2',
        'base_delivery_fee' => 'decimal:2',
        'base_delivery_km' => 'decimal:2',
        'per_km_delivery_fee' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'theme_settings' => 'array',
    ];

    // Relationships

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

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
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
        return $this->subdomain ? "{$this->subdomain}.".config('app.domain') : $this->slug;
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

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function activeCoupons(): HasMany
    {
        return $this->hasMany(Coupon::class)->active();
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function restaurantAdmin(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'restaurant_admin');
    }

    public function themeSettings(): HasOne
    {
        return $this->hasOne(RestaurantThemeSetting::class);
    }

    // Helper Methods
    public function getThemePath(): string
    {
        if (! $this->theme) {
            return 'burger-theme'; // الثيم الافتراضي
        }

        return $this->theme->folder_name;
    }

    public function getThemeSettings(): array
    {
        if (! $this->themeSettings) {
            return $this->theme->default_settings ?? [];
        }

        return $this->themeSettings->getMergedSettings();
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

    public function hasFeature(string $feature): bool
    {
        $plan = $this->plan ?? 'starter';

        return in_array(
            $feature,
            self::PLAN_FEATURES[$plan] ?? self::PLAN_FEATURES['starter'],
            true
        );
    }
}
