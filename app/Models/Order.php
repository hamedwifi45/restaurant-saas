<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property Carbon|null $driver_arrived_at
 */
class Order extends Model
{
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected $fillable = [
        'restaurant_id',
        'restaurant_table_id',
        'user_id',
        'driver_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'coupon_id',
        'coupon_code',
        'coupon_discount',
        'tax_amount',
        'delivery_type',
        'dine_in_arrival_at',
        'dine_in_duration_minutes',
        'reservation_status',
        'requested_pickup_at',
        'deposit_amount',
        'dine_in_guest_count',
        'dine_in_seating_preference',
        'dine_in_occasion',
        'dine_in_high_chair_count',
        'dine_in_stroller_space',
        'dine_in_accessibility_notes',
        'dine_in_allergy_notes',
        'dine_in_dietary_preference',
        'dine_in_confirmation_call',
        'dine_in_celebration_notes',
        'device_token',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'driver_latitude',
        'driver_longitude',
        'driver_location_updated_at',
        'final_amount',
        'payment_method',
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
        'customer_lat',
        'customer_lng',
        'distance_km',
        'tracking_code',
        'rating',
        'driver_rating',
        'review',
        'offer_id',
        'discount_amount',

    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'delivered_at' => 'datetime',
            'delivery_latitude' => 'decimal:7',
            'delivery_longitude' => 'decimal:7',
            'driver_latitude' => 'decimal:7',
            'driver_longitude' => 'decimal:7',
            'driver_location_updated_at' => 'datetime',
            'driver_arrived_at' => 'datetime',
            'dine_in_arrival_at' => 'datetime',
            'dine_in_duration_minutes' => 'integer',
            'requested_pickup_at' => 'datetime',
            'deposit_amount' => 'decimal:2',
            'dine_in_guest_count' => 'integer',
            'dine_in_high_chair_count' => 'integer',
            'dine_in_stroller_space' => 'boolean',
            'dine_in_confirmation_call' => 'boolean',
            'customer_lat' => 'decimal:7',
            'customer_lng' => 'decimal:7',
            'distance_km' => 'decimal:2',
            'driver_rating' => 'integer',
        ];
    }

    protected $appends = ['type'];

    public function getTypeAttribute()
    {
        return $this->attributes['delivery_type'] ?? 'delivery';
    }

    // العلاقة مع التقييم
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    // هل تم تقييم هذا الطلب؟
    public function getIsReviewedAttribute(): bool
    {
        return $this->review()->exists();
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function driverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    // العلاقة المباشرة مع البروفايل عبر user_id
    public function driver()
    {
        return $this->hasOneThrough(
            Driver::class,
            User::class,
            'id',        // المفتاح الخارجي في جدول users الذي يطابق orders.driver_id
            'user_id',   // المفتاح الخارجي في جدول drivers الذي يطابق users.id
            'driver_id', // المفتاح المحلي في جدول orders
            'id'         // المفتاح المحلي في جدول users
        );
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->restaurant_id) && auth()->check() && auth()->user()->restaurant_id) {
                $order->restaurant_id = auth()->user()->restaurant_id;
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(Order_item::class);
    }
}
