<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'author',
        'version',
        'description',
        'folder_name',
        'preview_image',
        'default_settings',
        'allowed_variables',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'default_settings' => 'array',
        'allowed_variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // العلاقات
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function themeSettings(): HasMany
    {
        return $this->hasMany(RestaurantThemeSetting::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Helper Methods
    public function getDefaultSetting(string $key, $default = null)
    {
        return $this->default_settings[$key] ?? $default;
    }

    public function getAllowedVariable(string $key, $default = null)
    {
        return $this->allowed_variables[$key] ?? $default;
    }

    public function getThemePath(): string
    {
        return resource_path("views/themes/{$this->folder_name}");
    }

    public function themeExists(): bool
    {
        return is_dir($this->getThemePath());
    }

    public function hasThemeJson(): bool
    {
        return file_exists($this->getThemePath() . '/theme.json');
    }

    public function loadThemeJson(): ?array
    {
        $path = $this->getThemePath() . '/theme.json';
        
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        return json_decode($content, true);
    }

}
