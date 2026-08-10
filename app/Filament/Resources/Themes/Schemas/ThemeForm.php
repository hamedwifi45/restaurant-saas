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
                    ->maxLength(255)
                    ->disabled(),

                TextInput::make('slug')
                    ->label('الرابط المختصر')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),

                TextInput::make('author')
                    ->label('المطور')
                    ->maxLength(255)
                    ->disabled(),

                TextInput::make('version')
                    ->label('الإصدار')
                    ->maxLength(50)
                    ->disabled(),

                Textarea::make('description')
                    ->label('الوصف')
                    ->disabled()
                    ->rows(3),

                FileUpload::make('preview_image')
                    ->label('صورة المعاينة')
                    ->image()
                    ->directory('themes/previews')
                    ->visibility('public'),

                Toggle::make('is_active')
                    ->label('تصميم مفعل')
                    ->default(false),

                Toggle::make('is_default')
                    ->label('تصميم افتراضي')
                    ->default(false),
            ]);
    }
}
