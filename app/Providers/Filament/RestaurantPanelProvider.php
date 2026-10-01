<?php

namespace App\Providers\Filament;

use App\Filament\Restaurant\Pages\FinancialAnalytics;
use App\Filament\Restaurant\Pages\RestaurantDashboard;
use App\Filament\Restaurant\Pages\RestaurantProfileSettings;
use App\Filament\Restaurant\Pages\RestaurantThemeSettings;
use App\Filament\Restaurant\Pages\StopImpersonation;
use App\Filament\Restaurant\Resources\Categories\CategoryResource;
use App\Filament\Restaurant\Resources\Coupons\CouponResource;
use App\Filament\Restaurant\Resources\Drivers\DriverResource;
use App\Filament\Restaurant\Resources\Expenses\ExpenseResource;
use App\Filament\Restaurant\Resources\Invoices\InvoiceResource;
use App\Filament\Restaurant\Resources\Offers\OfferResource;
use App\Filament\Restaurant\Resources\Orders\OrderResource;
use App\Filament\Restaurant\Resources\Products\ProductResource;
use App\Filament\Restaurant\Resources\RestaurantTables\RestaurantTableResource;
use App\Filament\Restaurant\Widgets\FinancialOverviewWidget;
use App\Filament\Restaurant\Widgets\OrderTypeChartWidget;
use App\Filament\Restaurant\Widgets\PaymentMethodChartWidget;
use App\Filament\Restaurant\Widgets\SalesChartWidget;
use App\Filament\Restaurant\Widgets\TopProductsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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

class RestaurantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('restaurant')
            ->path('restaurant')
            ->homeUrl(fn () => url('/restaurant'))
            ->login()
            ->brandName('لوحة المطاعم')
            ->colors([
                'primary' => Color::Orange,
                'gray' => Color::Zinc,
                'danger' => Color::Red,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Tajawal')
            ->spa() // تفعيل SPA
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->navigationGroups([
                NavigationGroup::make('التشغيل')->icon('heroicon-o-command-line'),
                NavigationGroup::make('القائمة')->icon('heroicon-o-clipboard-document-list'),
                NavigationGroup::make('التسويق')->icon('heroicon-o-megaphone'),
                NavigationGroup::make('الإعدادات')->icon('heroicon-o-cog-6-tooth'),
                NavigationGroup::make('التقارير والمالية')->icon('heroicon-o-chart-bar-square'),
            ])
            ->darkMode(false)
            ->viteTheme('resources/css/filament/restaurant/theme.css')
            ->resources([
                CategoryResource::class,
                ProductResource::class,
                OrderResource::class,
                InvoiceResource::class,
                ExpenseResource::class,
                OfferResource::class,
                CouponResource::class,
                DriverResource::class,
                RestaurantTableResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Restaurant/Resources'), for: 'App\\Filament\\Restaurant\\Resources')
            ->discoverPages(in: app_path('Filament/Restaurant/Pages'), for: 'App\\Filament\\Restaurant\\Pages')
            ->pages([
                RestaurantDashboard::class,
                FinancialAnalytics::class,
                RestaurantProfileSettings::class,
                RestaurantThemeSettings::class,
                StopImpersonation::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                FinancialOverviewWidget::class,
                TopProductsWidget::class,
                OrderTypeChartWidget::class,
                SalesChartWidget::class,
                PaymentMethodChartWidget::class,
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
            ]);
    }
}
