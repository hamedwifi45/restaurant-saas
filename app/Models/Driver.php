<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'rating',
        'vehicle_type',
        'plate_number',
        'avatar',
        'current_latitude',
        'current_longitude',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
