<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->record->getRoleNames()->first() ?? $this->record->role;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['role'] = $data['roles'];
        unset($data['roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncRoles([$this->record->role]);
    }
}
