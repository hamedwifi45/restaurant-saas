<?php

namespace App\Filament\Restaurant\Pages;

use App\Services\RestaurantDashboardService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use UnitEnum;

class RestaurantDashboard extends Page
{
    public string $view = 'filament.pages.restaurant-dashboard';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 1;

    public static function getSlug(?Panel $panel = null): string
    {
        return '';
    }

    public function getViewData(): array
    {
        $restaurant = auth('web')->user()?->restaurant;

        return [
            ...app(RestaurantDashboardService::class)->forRestaurant($restaurant),
            'restaurant' => $restaurant,
        ];
    }
}
