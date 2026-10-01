<?php

namespace App\Filament\Sham\Resources\Restaurants\Pages;

use App\Filament\Resources\Concerns\HandlesImageUrl;
use App\Filament\Sham\Resources\Restaurants\Pages\Concerns\ManagesRestaurantOwner;
use App\Filament\Sham\Resources\Restaurants\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurant extends CreateRecord
{
    use HandlesImageUrl;
    use ManagesRestaurantOwner;

    protected static string $resource = RestaurantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mergeImageUrls(
            $this->extractOwnerData($data),
            ['logo', 'cover_image', 'qr_code_image'],
        );
    }

    protected function afterCreate(): void
    {
        $this->saveRestaurantOwner($this->record);
    }
}
