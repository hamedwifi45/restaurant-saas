<?php

namespace App\Filament\Restaurant\Resources\Staff\Pages;

use App\Filament\Restaurant\Resources\Staff\StaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['restaurant_id'] = auth()->user()->restaurant_id;

        return $data;
    }
}
