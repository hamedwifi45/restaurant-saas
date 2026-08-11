<?php

namespace App\Filament\Resources\Themes\Pages;

use App\Filament\Resources\Themes\ThemeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThemes extends ListRecords
{
    protected static string $resource = ThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_theme')
                ->label('رفع ثيم جديد')
                ->url(static::getUrl('upload'))
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success'),
            CreateAction::make(),
        ];
    }
}
