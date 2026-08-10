<?php

namespace App\Filament\Resources\Themes\Tables;

use App\Models\Theme;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ThemesTable
{
    public static function configure(Table $table): Table
    {
      return $table
            ->columns([
                ImageColumn::make('preview_image')
                    ->label('معاينة')
                    ->size(80),

                TextColumn::make('name')
                    ->label('اسم السمة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('الرابط')
                    ->copyable(),

                TextColumn::make('version')
                    ->label('الإصدار')
                    ->sortable(),

                TextColumn::make('author')
                    ->label('المطور')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->query(fn ($q) => $q->where('is_active', true))
                    ->label('التصاميم المفعلة فقط'),
                    
                Filter::make('is_default')
                    ->query(fn ($q) => $q->where('is_default', true))
                    ->label('التصميم الافتراضي'),
                    
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Theme $record): bool => !$record->is_active)
                    ->action(function (Theme $record): void {
                        $record->update(['is_active' => true]);
                        
                        Notification::make()
                            ->title('تم التفعيل')
                            ->body('تم تفعيل الثيم بنجاح')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-m-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Theme $record): bool => $record->is_active && !$record->is_default)
                    ->action(function (Theme $record): void {
                        $record->update(['is_active' => false]);
                        
                        Notification::make()
                            ->title('تم الإيقاف')
                            ->body('تم إيقاف الثيم بنجاح')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('setDefault')
                    ->label('جعل افتراضي')
                    ->icon('heroicon-m-star')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('جعل هذا الثيم افتراضياً؟')
                    ->modalDescription('سيتم تعيين هذا الثيم كافتراضي لجميع المطاعم الجديدة')
                    ->visible(fn (Theme $record): bool => $record->is_active && !$record->is_default)
                    ->action(function (Theme $record): void {
                        // Remove default status from all other themes
                        Theme::where('is_default', true)->update(['is_default' => false]);
                        
                        $record->update(['is_default' => true]);
                        
                        Notification::make()
                            ->title('تم التعيين كافتراضي')
                            ->body('تم تعيين هذا الثيم كافتراضي بنجاح')
                            ->success()
                            ->send();
                    }),
                    
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('حذف الثيم')
                    ->modalDescription('هل أنت متأكد من حذف هذا الثيم؟ سيتم حذف جميع الملفات المرتبطة به.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد تصاميم')
            ->emptyStateDescription('قم برفع ثيم جديد للبدء');
     }
}
