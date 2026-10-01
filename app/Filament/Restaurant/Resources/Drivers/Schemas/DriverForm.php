<?php

namespace App\Filament\Restaurant\Resources\Drivers\Schemas;

use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DriverForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات السائق الحسابية')
                ->schema([
                    Select::make('existing_user_id')
                        ->label('ربط بحساب مندوب موجود')
                        ->options(fn (): array => User::query()
                            ->where('role', 'driver')
                            ->where('restaurant_id', auth()->user()->restaurant_id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            if (blank($state)) {
                                return;
                            }

                            $user = User::query()
                                ->where('role', 'driver')
                                ->where('restaurant_id', auth()->user()->restaurant_id)
                                ->whereKey($state)
                                ->first();

                            if ($user) {
                                $set('name', $user->name);
                                $set('email', $user->email);
                                $set('phone', $user->phone);
                            }
                        })
                        ->visible(fn (string $operation): bool => $operation === 'create'),
                    TextInput::make('name')
                        ->label('اسم السائق')
                        ->disabled(fn (Get $get, string $operation): bool => $operation === 'create' && filled($get('existing_user_id')))
                        ->required(fn (Get $get, string $operation): bool => $operation === 'edit' || blank($get('existing_user_id'))),
                    TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->disabled(fn (Get $get, string $operation): bool => $operation === 'create' && filled($get('existing_user_id')))
                        ->nullable(),
                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->disabled(fn (Get $get, string $operation): bool => $operation === 'create' && filled($get('existing_user_id')))
                        ->required(fn (Get $get, string $operation): bool => $operation === 'edit' || blank($get('existing_user_id')))
                        ->rules(fn (Get $get, string $operation, ?User $record): array => $operation === 'create' && filled($get('existing_user_id'))
                            ? []
                            : [Rule::unique('users', 'email')->ignore($record?->getKey())]),
                    TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->disabled(fn (Get $get, string $operation): bool => $operation === 'create' && filled($get('existing_user_id')))
                        ->required(fn (Get $get, string $operation): bool => $operation === 'create' && blank($get('existing_user_id')))
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                ])
                ->columns(2),
            Section::make('بيانات مركبة التوصيل')
                ->schema([
                    Select::make('vehicle_type')
                        ->label('نوع المركبة')
                        ->options([
                            'motorcycle' => 'دراجة نارية (موتور)',
                            'car' => 'سيارة توصيل',
                            'bicycle' => 'دراجة هوائية',
                        ])
                        ->default('motorcycle')
                        ->required(),
                    TextInput::make('plate_number')
                        ->label('رقم اللوحة / الترخيص')
                        ->required(),
                    TextInput::make('vehicle_model')
                        ->label('موديل ولون المركبة')
                        ->placeholder('مثال: سوزوكي حمراء')
                        ->nullable(),
                ])
                ->columns(2),
            Section::make('الحالة التشغيلية والتصنيف')
                ->schema([
                    Toggle::make('is_available')
                        ->label('حالة التفرغ')
                        ->helperText('مفعل: متاح للتوصيل. غير مفعل: مشغول/خارج الخدمة.')
                        ->default(true)
                        ->required(),
                    Placeholder::make('rating_display')
                        ->label('التقييم')
                        ->content(fn (?User $record): string => $record?->driverProfile?->rating !== null
                            ? '★ '.number_format((float) $record->driverProfile->rating, 1).' / 5.0'
                            : '★ 5.0 / 5.0'),
                ])
                ->columns(2),
        ]);
    }
}
