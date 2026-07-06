<?php

namespace App\Filament\Resources\Restaurants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RestaurantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المطعم')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('subdomain')
                    ->label('Subdomain')
                    ->searchable(),
                TextColumn::make('logo')
                    ->label('شعار')
                    ->searchable(),
                ImageColumn::make('cover_image')
                    ->label('صورة الغلاف'),
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('مدينة')
                    ->searchable(),
                TextColumn::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_delivery_time')
                    ->label('وقت التوصيل المقدر')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('theme_id')
                    ->label('Theme ID')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('primary_color')
                    ->label('اللون الأساسي')
                    ->searchable(),
                TextColumn::make('secondary_color')
                    ->label('اللون الثانوي')
                    ->searchable(),
                TextColumn::make('background_color')
                    ->label('لون الخلفية')
                    ->searchable(),
                ImageColumn::make('qr_code_image')
                    ->label('صورة الكود QR'),
                TextColumn::make('pricing_type')
                    ->label('نوع التسعير')
                    ->searchable(),
                TextColumn::make('commission_rate')
                    ->label('معدل العمولة')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subscription_fee')
                    ->label('رسوم الاشتراك')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                TextColumn::make('trial_ends_at')
                    ->label('نهاية الفترة التجريبية')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('تاريخ الحذف')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
