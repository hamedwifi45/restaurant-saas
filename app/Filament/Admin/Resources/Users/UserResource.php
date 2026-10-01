<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Restaurant;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    private const ROLE_OPTIONS = [
        'super_admin' => 'مدير النظام',
        'restaurant_admin' => 'مدير مطعم',
        'kitchen_staff' => 'موظف مطبخ',
        'driver' => 'مندوب توصيل',
    ];

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'إدارة جميع الحسابات والموظفين';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المنصة';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'جميع المستخدمين';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الحساب')
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null),
                        Select::make('roles')
                            ->label('الدور')
                            ->options(fn (): array => Role::query()
                                ->where('guard_name', 'web')
                                ->whereIn('name', array_keys(self::ROLE_OPTIONS))
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->all())
                            ->required()
                            ->searchable()
                            ->native(false),
                        Select::make('restaurant_id')
                            ->label('المطعم التابع له')
                            ->options(fn (): array => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('الدور')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'restaurant_admin' => 'primary',
                        'kitchen_staff' => 'warning',
                        'driver' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('restaurant.name')
                    ->label('المطعم')
                    ->default('حساب منصة عام')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('الدور')
                    ->options(self::ROLE_OPTIONS)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('roles', fn (Builder $rolesQuery) => $rolesQuery->where('name', $data['value']))
                        : $query),
                SelectFilter::make('restaurant')
                    ->label('المطعم')
                    ->relationship('restaurant', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->with(['restaurant', 'roles']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
