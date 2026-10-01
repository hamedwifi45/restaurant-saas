<?php

namespace App\Filament\Restaurant\Resources\Staff\Pages;

use App\Filament\Restaurant\Resources\Staff\StaffResource;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['restaurant_id'] = auth()->user()->restaurant_id;

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
