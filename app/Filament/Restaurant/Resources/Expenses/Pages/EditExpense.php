<?php

namespace App\Filament\Restaurant\Resources\Expenses\Pages;

use App\Filament\Restaurant\Resources\Expenses\ExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['restaurant_id'] = Auth::user()->restaurant_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
