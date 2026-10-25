<?php

namespace App\Filament\Sham\Resources\DeliveryProviders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات شركة التوصيل')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الشركة')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_name')
                            ->label('اسم جهة التواصل')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(32),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Select::make('assignment_method')
                            ->label('طريقة الإحالة')
                            ->options(['manual' => 'إحالة يدوية'])
                            ->default('manual')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('الشركة نشطة')
                            ->default(true),
                        Textarea::make('pricing_notes')
                            ->label('ملاحظات شروط التسعير')
                            ->helperText('مرجع إداري فقط. تُسجّل أجرة الشركة لكل إحالة ولا تُضاف تلقائياً إلى فاتورة العميل.')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
