<?php

namespace App\Filament\Resources\Themes\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ThemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم التصميم')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label('الرابط المختصر')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('الوصف'),

                FileUpload::make('preview_image')
                    ->label('صورة المعاينة')
                    ->image()
                    ->directory('themes/previews'),

                Toggle::make('is_active')
                    ->label('تصميم مفعل')
                    ->default(true),
            ]);
    }
}
