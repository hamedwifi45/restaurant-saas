<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Theme extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'preview_image',
        'author',
        'version',
        'sections',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'sections' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    // الحصول على مسار الثيم
    public function getViewPath(): string
    {
        return "themes.{$this->slug}";
    }

    // الحصول على الأقسام المتاحة
    public function getAvailableSections(): array
    {
        return $this->sections ?? ['hero', 'menu', 'about', 'contact'];
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

}
