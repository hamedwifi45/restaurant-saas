<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryDispatchEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_dispatch_id',
        'actor_id',
        'from_status',
        'to_status',
        'note',
    ];

    /**
     * @return BelongsTo<DeliveryDispatch, $this>
     */
    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(DeliveryDispatch::class, 'delivery_dispatch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
