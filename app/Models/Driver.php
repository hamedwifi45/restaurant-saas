<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    protected $attributes = [
        'vehicle_type' => 'motorcycle',
        'is_available' => true,
        'rating' => 5.0,
    ];

    protected $fillable = [
        'user_id',
        'phone',
        'rating',
        'vehicle_type',
        'vehicle_model',
        'plate_number',
        'is_available',
        'avatar',
        'current_latitude',
        'current_longitude',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'is_available' => 'boolean',
            'current_latitude' => 'decimal:8',
            'current_longitude' => 'decimal:8',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
