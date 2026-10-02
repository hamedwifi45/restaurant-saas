<?php

namespace App\Filament\Sham\Resources\Products\Pages;

use App\Filament\Sham\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
