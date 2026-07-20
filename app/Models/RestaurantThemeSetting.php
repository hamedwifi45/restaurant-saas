<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantThemeSetting extends Model
{
     protected $fillable = [
        'restaurant_id',
        'theme_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    // العلاقات
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    // Helper Methods
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    public function getMergedSettings(): array
    {
        $defaultSettings = $this->theme->default_settings ?? [];
        $customSettings = $this->settings ?? [];

        return array_merge($defaultSettings, $customSettings);
    }

    public function resetToDefaults(): void
    {
        $this->settings = $this->theme->default_settings ?? [];
        $this->save();
    }
}
