<?php

namespace App\Filament\Sham\Resources\DeliveryProviders\Pages;

use App\Filament\Sham\Resources\DeliveryProviders\DeliveryProviderResource;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryProvider extends EditRecord
{
    protected static string $resource = DeliveryProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
