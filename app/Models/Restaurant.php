<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'is_accepting_orders' => true,
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
        'opening_time',
        'closing_time',
        'is_accepting_orders',
        'trial_ends_at',
        'latitude',
        'longitude',
        'delivery_radius_km',
        'base_delivery_fee',
        'base_delivery_km',
        'per_km_delivery_fee',
        'service_config',
        'experimental_order_features',
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
        'is_accepting_orders' => 'boolean',
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
        'service_config' => 'array',
        'experimental_order_features' => 'array',
    ];

    public const EXPERIMENTAL_ORDER_FEATURE_DEFAULTS = [
        'table_reservations' => false,
        'scheduled_pickup' => false,
        'deposit_percentage' => 20,
        'reservation_duration_minutes' => 90,
        'reservation_duration_options' => [90],
        'reservation_seating_preference' => false,
        'reservation_occasion' => false,
        'reservation_high_chairs' => false,
        'reservation_stroller_space' => false,
        'reservation_accessibility_notes' => false,
        'reservation_allergy_notes' => false,
        'reservation_dietary_preference' => false,
        'reservation_confirmation_call' => false,
        'reservation_celebration_notes' => false,
        'risk_acknowledged' => false,
    ];

    public const ORDER_SERVICE_DEFAULTS = [
        'delivery' => true,
        'own_delivery_drivers' => true,
        'external_delivery' => false,
        'takeaway' => true,
        'dine_in' => true,
        'table_reservations' => false,
        'scheduled_pickup' => false,
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

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<RestaurantTable, $this>
     */
    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    /**
     * @return BelongsToMany<DeliveryProvider, $this>
     */
    public function deliveryProviders(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryProvider::class)
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<DeliveryProvider, $this>
     */
    public function eligibleDeliveryProviders(): BelongsToMany
    {
        return $this->deliveryProviders()
            ->wherePivot('is_enabled', true)
            ->where('delivery_providers.is_active', true)
            ->where('delivery_providers.assignment_method', 'manual');
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

    /**
     * @return HasMany<Offer, $this>
     */
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

    /**
     * @return array<string, bool|int|float|string|null>
     */
    public function getExperimentalOrderFeatures(): array
    {
        $legacyFeatures = $this->experimental_order_features ?? [];
        $serviceConfig = $this->service_config ?? [];

        $features = array_replace(
            self::EXPERIMENTAL_ORDER_FEATURE_DEFAULTS,
            $legacyFeatures,
            [
                'table_reservations' => (bool) ($serviceConfig['table_reservations'] ?? $legacyFeatures['table_reservations'] ?? false),
                'scheduled_pickup' => (bool) ($serviceConfig['scheduled_pickup'] ?? $legacyFeatures['scheduled_pickup'] ?? false),
            ],
        );

        $features['reservation_duration_options'] = collect($features['reservation_duration_options'])
            ->map(fn (mixed $duration): int => (int) $duration)
            ->filter(fn (int $duration): bool => in_array($duration, [30, 60, 90, 120, 150, 180, 240], true))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $features;
    }

    public function experimentalOrderFeatureEnabled(string $feature): bool
    {
        if (in_array($feature, ['delivery', 'takeaway', 'dine_in', 'table_reservations', 'scheduled_pickup'], true)) {
            return $this->serviceEnabled($feature);
        }

        return (bool) ($this->getExperimentalOrderFeatures()[$feature] ?? false);
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

    /**
     * @return array<string, bool>
     */
    public function getServiceConfig(): array
    {
        $legacyFeatures = $this->experimental_order_features ?? [];
        $config = array_replace_recursive(
            self::ORDER_SERVICE_DEFAULTS,
            [
                'table_reservations' => (bool) ($legacyFeatures['table_reservations'] ?? false),
                'scheduled_pickup' => (bool) ($legacyFeatures['scheduled_pickup'] ?? false),
            ],
            $this->service_config ?? [],
        );

        $config['delivery'] = (bool) ($config['delivery'] ?? true);
        $config['own_delivery_drivers'] = (bool) ($config['own_delivery_drivers'] ?? true);
        $config['external_delivery'] = (bool) ($config['external_delivery'] ?? false);
        $config['takeaway'] = (bool) ($config['takeaway'] ?? true);
        $config['dine_in'] = (bool) ($config['dine_in'] ?? true);
        $config['table_reservations'] = (bool) ($config['table_reservations'] ?? false);
        $config['scheduled_pickup'] = (bool) ($config['scheduled_pickup'] ?? false);

        return $config;
    }

    public function serviceEnabled(string $service): bool
    {
        $config = $this->getServiceConfig();

        return (bool) ($config[$service] ?? false);
    }

    public function serviceDisabled(string $service): bool
    {
        return ! $this->serviceEnabled($service);
    }

    public function diningServiceEnabled(): bool
    {
        return $this->serviceEnabled('dine_in');
    }

    public function ownDeliveryDriversEnabled(): bool
    {
        return $this->serviceEnabled('own_delivery_drivers');
    }

    public function externalDeliveryEnabled(): bool
    {
        return $this->serviceEnabled('external_delivery');
    }

    public function deliveryFulfillmentAvailable(): bool
    {
        if (! $this->serviceEnabled('delivery')) {
            return false;
        }

        return $this->ownDeliveryDriversEnabled()
            || ($this->externalDeliveryEnabled() && $this->eligibleDeliveryProviders()->exists());
    }

    public function reservationsEnabled(): bool
    {
        return $this->serviceEnabled('table_reservations');
    }

    public function scheduledPickupEnabled(): bool
    {
        return $this->serviceEnabled('scheduled_pickup');
    }

    public function canAcceptOrders(): bool
    {
        if (! $this->is_accepting_orders) {
            return false;
        }

        if (! $this->opening_time || ! $this->closing_time) {
            return true;
        }

        $currentTime = now()->format('H:i');
        $openingTime = substr((string) $this->opening_time, 0, 5);
        $closingTime = substr((string) $this->closing_time, 0, 5);

        if ($openingTime === $closingTime) {
            return false;
        }

        if ($openingTime < $closingTime) {
            return $currentTime >= $openingTime && $currentTime < $closingTime;
        }

        return $currentTime >= $openingTime || $currentTime < $closingTime;
    }

    public function orderUnavailableMessage(): string
    {
        if (! $this->is_accepting_orders) {
            return 'نعتذر منكم، المطعم يمر بفترة زحمة حالياً وتوقف عن استقبال الطلبات مؤقتاً. يرجى المحاولة بعد قليل.';
        }

        $openingTime = substr((string) $this->opening_time, 0, 5);
        $closingTime = substr((string) $this->closing_time, 0, 5);

        return "عذراً، المطعم مغلق حالياً ولا يمكن استقبال الطلبات في هذا الوقت. أوقات العمل من {$openingTime} إلى {$closingTime}.";
    }
}
