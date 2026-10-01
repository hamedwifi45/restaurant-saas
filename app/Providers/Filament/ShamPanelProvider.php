<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Sham\Resources\Categories\CategoryResource;
use App\Filament\Sham\Resources\Invoices\InvoiceResource;
use App\Filament\Sham\Resources\Orders\OrderResource;
use App\Filament\Sham\Resources\Products\ProductResource;
use App\Filament\Sham\Resources\Restaurants\RestaurantResource;
use App\Filament\Sham\Resources\Themes\ThemeResource;
use App\Filament\Sham\Widgets\SystemOverviewWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ShamPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('sham')
            ->path('sham')
            ->viteTheme('resources/css/filament/sham/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Orange,
                'gray' => Color::Zinc,
                'danger' => Color::Red,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Tajawal')
            ->spa() // تفعيل Single Page Application للأنيميشن
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('إدارة المنصة'),
            ])
            ->darkMode(false)
            ->resources([
                UserResource::class,
                RestaurantResource::class,
                CategoryResource::class,
                ProductResource::class,
                OrderResource::class,
                ThemeResource::class,
                InvoiceResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Sham/Resources'), for: 'App\\Filament\\Sham\\Resources')
            ->discoverPages(in: app_path('Filament/Sham/Pages'), for: 'App\\Filament\\Sham\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                SystemOverviewWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]); // إضافة الثيم المخصص
    }
}
