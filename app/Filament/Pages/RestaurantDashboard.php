<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Panel;

class RestaurantDashboard extends Page
{
    public string $view = 'filament.pages.restaurant-dashboard';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $slug = 'dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function getSlug(?Panel $panel = null): string
    {
        return static::$slug;
    }
}
