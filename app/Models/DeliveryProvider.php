<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'phone',
        'email',
        'pricing_notes',
        'assignment_method',
        'is_active',
        'pricing_model',
        'base_fee',
        'base_distance_km',
        'per_km_fee',
        'min_distance_km',
        'max_distance_km',
        'min_order_amount',
        'estimated_pickup_minutes',
        'road_detour_factor',
        'allowed_zones',
        'blocked_zones',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_fee' => 'decimal:2',
        'base_distance_km' => 'decimal:2',
        'per_km_fee' => 'decimal:2',
        'min_distance_km' => 'decimal:2',
        'max_distance_km' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'estimated_pickup_minutes' => 'integer',
        'road_detour_factor' => 'decimal:2',
        'allowed_zones' => 'array',
        'blocked_zones' => 'array',
    ];

    /**
     * @return BelongsToMany<Restaurant, $this>
     */
    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class)
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /**
     * @return HasMany<DeliveryDispatch, $this>
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(DeliveryDispatch::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
