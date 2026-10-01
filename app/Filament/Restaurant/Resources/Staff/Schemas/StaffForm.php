<?php

namespace App\Filament\Restaurant\Resources\Staff\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('اسم الموظف')
                ->required(),
            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('phone')
                ->label('رقم الهاتف')
                ->tel()
                ->nullable()
                ->rule('regex:/^(09|\+?9639)\d{8}$/'),
            TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null),
            Select::make('role')
                ->label('الصفة / الدور')
                ->options([
                    'kitchen_staff' => 'طباخ / كادر المطبخ (KDS)',
                    'driver' => 'مندوب توصيل',
                    'restaurant_admin' => 'مدير فرعي',
                ])
                ->required(),
            Toggle::make('is_active')
                ->label('حساب نشط')
                ->default(true)
                ->required(),
        ]);
    }
}
