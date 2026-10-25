<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryDispatch extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return [
            self::STATUS_QUEUED,
            self::STATUS_ACCEPTED,
            self::STATUS_PICKED_UP,
        ];
    }

    protected $fillable = [
        'order_id',
        'delivery_provider_id',
        'requested_by',
        'status',
        'customer_delivery_fee',
        'provider_fee',
        'currency_symbol',
        'provider_reference',
        'notes',
        'queued_at',
        'accepted_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'customer_delivery_fee' => 'decimal:2',
        'provider_fee' => 'decimal:2',
        'queued_at' => 'datetime',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<DeliveryProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(DeliveryProvider::class, 'delivery_provider_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return HasMany<DeliveryDispatchEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(DeliveryDispatchEvent::class);
    }
}
