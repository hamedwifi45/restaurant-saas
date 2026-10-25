<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantThemeSetting;
use App\Models\User;
use App\Observers\OrderObserver;
use App\Observers\RestaurantObserver;
use App\Observers\RestaurantThemeSettingObserver;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Services\ThemeDiscoveryService;
use App\Services\ThemeUploadService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeDiscoveryService::class, function () {
            return new ThemeDiscoveryService;
        });

        $this->app->singleton(ThemeUploadService::class, function () {
            return new ThemeUploadService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // إجبار النظام على توليد روابط آمنة إذا كنا نستخدم ngrok
        if (request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }
        $this->configureDefaults();
        $this->registerPolicies();
        Restaurant::observe(RestaurantObserver::class);
        Order::observe(OrderObserver::class);
        RestaurantThemeSetting::observe(RestaurantThemeSettingObserver::class);
        Gate::define('access-kds', function (User $user): bool {
            $restaurant = $user->restaurant;
            $hostRestaurant = request()->attributes->get('restaurant');

            return $restaurant instanceof Restaurant
                && $restaurant->is_active
                && in_array($user->role, ['restaurant_admin', 'kitchen_staff'], true)
                && $restaurant->hasFeature('kds')
                && ($hostRestaurant === null
                    || ($hostRestaurant instanceof Restaurant && (int) $hostRestaurant->id === (int) $restaurant->id));
        });
        Gate::define('access-pos', function (User $user, string $slug): bool {
            if (! $user->is_active
                || ! $user->restaurant_id
                || (! $user->isRestaurantAdmin() && $user->role !== 'cashier')) {
                return false;
            }

            $hostRestaurant = request()->attributes->get('restaurant');
            if ($hostRestaurant instanceof Restaurant && (int) $hostRestaurant->id !== (int) $user->restaurant_id) {
                return false;
            }

            return Restaurant::query()
                ->whereKey($user->restaurant_id)
                ->where('slug', $slug)
                ->where('is_active', true)
                ->exists();
        });
        Gate::define('access-driver-panel', function (User $user, ?string $slug = null): bool {
            $restaurant = $slug
                ? Restaurant::query()->where('slug', $slug)->first()
                : $user->restaurant;

            return $restaurant && $user->canAccessDriverPanelFor($restaurant);
        });
        Gate::define('access-driver-dashboard', function (User $user, ?string $slug = null): bool {
            $restaurant = $slug
                ? Restaurant::query()->where('slug', $slug)->first()
                : $user->restaurant;

            return $restaurant && $user->canManageDriverDashboardFor($restaurant);
        });
        Gate::define('access-driver-orders', function (User $user, ?string $slug = null): bool {
            $restaurant = $slug
                ? Restaurant::query()->where('slug', $slug)->first()
                : $user->restaurant;

            return $restaurant && $user->canViewDriverOrdersFor($restaurant);
        });
        Gate::define('access-theme-editor', fn (User $user): bool => $user->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('theme_editor') ?? false)
        );
        Gate::define('advanced-financial-analytics', fn (User $user): bool => $user->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false)
        );
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
