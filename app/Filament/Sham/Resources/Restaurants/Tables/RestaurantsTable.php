<?php

namespace App\Filament\Sham\Resources\Restaurants\Tables;

use App\Filament\Sham\Resources\Restaurants\Actions\ImpersonateRestaurantAdmin;
use App\Models\Restaurant;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class RestaurantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->label('الشعار')->disk('public')->circular(),
                TextColumn::make('name')->label('اسم المطعم')->searchable()->sortable(),
                TextColumn::make('restaurantAdmin.name')->label('اسم المالك')->searchable(),
                TextColumn::make('restaurantAdmin.email')->label('بريد المالك')->searchable(),
                TextColumn::make('plan')
                    ->label('الباقة')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'pro' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                ToggleColumn::make('is_active')->label('نشط'),
                TextColumn::make('subdomain')
                    ->label('الرابط')
                    ->formatStateUsing(fn (?string $state, Restaurant $record): string => $state ?: $record->slug)
                    ->url(fn (Restaurant $record): string => route('restaurant.home', ['slug' => $record->slug]))
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('changePlan')
                    ->label('تغيير الباقة')
                    ->icon('heroicon-o-sparkles')
                    ->form([
                        Select::make('plan')
                            ->label('الباقة الجديدة')
                            ->options(['starter' => 'Starter', 'pro' => 'Pro'])
                            ->required(),
                    ])
                    ->fillForm(fn (Restaurant $record): array => ['plan' => $record->plan])
                    ->action(function (Restaurant $record, array $data): void {
                        $record->update(['plan' => $data['plan']]);
                        Notification::make()->title('تم تغيير باقة المطعم')->success()->send();
                    }),
                Action::make('toggleStatus')
                    ->label(fn (Restaurant $record): string => $record->is_active ? 'إيقاف الحساب' : 'تفعيل الحساب')
                    ->icon(fn (Restaurant $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (Restaurant $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Restaurant $record): void {
                        $isActive = ! $record->is_active;
                        $record->update(['is_active' => $isActive]);

                        Notification::make()
                            ->title($isActive ? 'تم تفعيل حساب المطعم' : 'تم إيقاف حساب المطعم')
                            ->color($isActive ? 'success' : 'warning')
                            ->send();
                    }),
                Action::make('impersonate')
                    ->label('تسجيل الدخول كمدير مطعم')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->visible(fn (Restaurant $record): bool => $record->is_active && $record->restaurantAdmin !== null)
                    ->requiresConfirmation()
                    ->modalDescription('سيتم فتح لوحة المطعم بحساب المالك، ويمكنك العودة إلى لوحة الإدارة من خيار إنهاء تسجيل الدخول بالنيابة.')
                    ->action(fn (Restaurant $record) => app(ImpersonateRestaurantAdmin::class)->handle($record)),
                EditAction::make(),
            ]);
    }
}
