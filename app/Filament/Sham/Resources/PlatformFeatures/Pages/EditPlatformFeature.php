<?php

namespace App\Filament\Sham\Resources\PlatformFeatures\Pages;

use App\Filament\Sham\Resources\PlatformFeatures\PlatformFeatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformFeature extends EditRecord
{
    protected static string $resource = PlatformFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
