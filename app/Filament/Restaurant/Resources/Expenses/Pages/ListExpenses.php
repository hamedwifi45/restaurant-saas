<?php

namespace App\Filament\Restaurant\Resources\Expenses\Pages;

use App\Filament\Restaurant\Resources\Expenses\ExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة مصروف'),
        ];
    }
}
