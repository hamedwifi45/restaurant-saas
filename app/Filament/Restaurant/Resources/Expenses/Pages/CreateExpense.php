<?php

namespace App\Filament\Restaurant\Resources\Expenses\Pages;

use App\Filament\Restaurant\Resources\Expenses\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['restaurant_id'] = Auth::user()->restaurant_id;

        return $data;
    }
}
