<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'path',
        'alt',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // العلاقة العكسية (يمكن أن تنتمي لأي موديل)
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    // الحصول على الرابط الكامل للصورة
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
