<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property-read Restaurant|null $restaurant
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'addreas', 'phone', 'city', 'restaurant_id', 'is_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'restaurant' => $this->role === 'super_admin'
                || ($this->role === 'restaurant_admin'
                    && filled($this->restaurant_id)
                    && $this->restaurant()->where('is_active', true)->exists()),
            'sham' => $this->role === 'super_admin',
            default => false,
        };
    }

    /**
     * @return BelongsTo<Restaurant, $this>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * @return HasOne<Driver, $this>
     */
    public function driverProfile(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    public function canAccessDriverPanelFor(Restaurant $restaurant): bool
    {
        return ! $this->isRestaurantAdmin()
            && (int) $this->restaurant_id === (int) $restaurant->getKey()
            && (
                $this->role === 'driver'
                || $this->hasRole('driver')
                || $this->getAllPermissions()->contains('name', 'access-driver-panel')
            );
    }

    public function canViewDriverOrdersFor(Restaurant $restaurant): bool
    {
        return (int) $this->restaurant_id === (int) $restaurant->getKey()
            && $this->canAccessDriverPanelFor($restaurant);
    }

    public function canManageDriverDashboardFor(Restaurant $restaurant): bool
    {
        return $this->role === 'super_admin'
            || $this->hasRole('super_admin')
            || (
                ($this->isRestaurantAdmin() || $this->role === 'restaurant_owner')
                && (int) $this->restaurant_id === (int) $restaurant->getKey()
            );
    }

    public function isRestaurantAdmin(): bool
    {
        return $this->role === 'restaurant_admin' || $this->hasRole('restaurant_admin');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
