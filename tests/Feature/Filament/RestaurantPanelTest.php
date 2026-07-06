<?php

use App\Providers\Filament\RestaurantPanelProvider;
use Illuminate\Container\Container;
use Filament\Panel;

it('registers a dedicated restaurant panel with the expected route', function () {
    $app = Container::getInstance();
    $provider = new RestaurantPanelProvider($app);

    $panel = $provider->panel(Panel::make());

    expect($panel->getId())->toBe('restaurant')
        ->and($panel->getPath())->toBe('restaurant');
});
