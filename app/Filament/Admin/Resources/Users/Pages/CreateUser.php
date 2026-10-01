<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = $data['roles'];
        unset($data['roles']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncRoles([$this->record->role]);
    }
}
