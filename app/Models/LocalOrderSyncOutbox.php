<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalOrderSyncOutbox extends Model
{
    protected $table = 'local_order_sync_outbox';

    protected $fillable = [
        'restaurant_id',
        'order_id',
        'event_uuid',
        'payload',
        'status',
        'attempts',
        'next_attempt_at',
        'claimed_at',
        'cloud_order_id',
        'last_error',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'next_attempt_at' => 'datetime',
            'claimed_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
