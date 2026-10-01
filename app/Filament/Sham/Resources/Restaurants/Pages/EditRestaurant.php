<?php

namespace App\Filament\Sham\Resources\Restaurants\Pages;

use App\Filament\Resources\Concerns\HandlesImageUrl;
use App\Filament\Sham\Resources\Restaurants\Pages\Concerns\ManagesRestaurantOwner;
use App\Filament\Sham\Resources\Restaurants\RestaurantResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurant extends EditRecord
{
    use HandlesImageUrl;
    use ManagesRestaurantOwner;

    protected static string $resource = RestaurantResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $owner = $this->record->restaurantAdmin;

        return [
            ...$data,
            'owner_name' => $owner?->name,
            'owner_email' => $owner?->email,
            'owner_phone' => $owner?->phone,
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mergeImageUrls(
            $this->extractOwnerData($data),
            ['logo', 'cover_image', 'qr_code_image'],
        );
    }

    protected function afterSave(): void
    {
        $this->saveRestaurantOwner($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
