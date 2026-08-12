<?php

namespace App\Filament\Resources\Themes\Tables;

use App\Models\Theme;
use App\Services\ThemeUploadService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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

                IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('upload_theme')
                    ->label('رفع ثيم ZIP')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('رفع وتثبيت ثيم جديد')
                    ->modalDescription('يجب أن يحتوي الملف على theme.json وملفات العرض الأساسية، وسيتحقق النظام تلقائياً من صلاحية الثيم قبل تثبيته.')
                    ->form([
                        FileUpload::make('zip_file')
                            ->label('ملف ZIP للثيم')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                            ->maxSize(20480)
                            ->disk('local')
                            ->directory('theme-uploads')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $path = Storage::disk('local')->path($data['zip_file']);
                        $result = app(ThemeUploadService::class)->upload($path);

                        if (! $result['success']) {
                            Notification::make()
                                ->title('فشل في رفع الثيم')
                                ->body($result['message'])
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('تم رفع الثيم بنجاح')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Filter::make('is_active')
                    ->query(fn ($q) => $q->where('is_active', true))
                    ->label('التصاميم المفعلة فقط'),
            ])
            ->actions([
                Action::make('preview')
                    ->label('معاينة حية')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Theme $record) => route('themes.preview', $record), true)
                    ->color('info')
                    ->openUrlInNewTab(),

                Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->hidden(fn (Theme $record): bool => $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Theme $record): void {
                        $record->activate();

                        Notification::make()
                            ->title('تم تفعيل الثيم')
                            ->body('تم تعيين '.$record->name.' كالثيم النشط.')
                            ->success()
                            ->send();
                    }),

                Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-o-x-circle')
                    ->hidden(fn (Theme $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Theme $record): void {
                        $record->deactivate();

                        Notification::make()
                            ->title('تم إيقاف الثيم')
                            ->body('تم تعطيل '.$record->name.' مؤقتاً.')
                            ->warning()
                            ->send();
                    }),

                Action::make('clone')
                    ->label('نسخ')
                    ->icon('heroicon-o-document-duplicate')
                    ->form([
                        TextInput::make('name')
                            ->label('اسم النسخة')
                            ->default(fn (Theme $record) => $record->name.' نسخة'),
                    ])
                    ->action(function (array $data, Theme $record): void {
                        $result = app(ThemeUploadService::class)->cloneTheme($record, $data['name'] ?? null);

                        Notification::make()
                            ->title($result['success'] ? 'تم نسخ الثيم' : 'فشل النسخ')
                            ->body($result['message'])
                            ->color($result['success'] ? 'success' : 'danger')
                            ->send();
                    }),

                Action::make('reset_settings')
                    ->label('إعادة تعيين الإعدادات')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('هل أنت متأكد؟')
                    ->modalDescription('سيتم حذف جميع الإعدادات المخصصة المرتبطة بهذا الثيم لجميع المطاعم.')
                    ->action(function (Theme $record): void {
                        $deleted = $record->resetSettingsForRestaurants();

                        Notification::make()
                            ->title('تمت إعادة تعيين الإعدادات')
                            ->body('تم حذف '.$deleted.' إعدادات مخصصة مرتبطة بهذه الثيم.')
                            ->success()
                            ->send();
                    }),

                Action::make('delete')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف الثيم')
                    ->modalDescription('هل أنت متأكد من حذف هذا الثيم؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->action(function (Theme $record): void {
                        if ($record->restaurants()->count() > 0) {
                            Notification::make()
                                ->title('لا يمكن الحذف')
                                ->body('يوجد مطاعم تستخدم هذا الثيم حالياً. يجب نقل المطاعم إلى ثيم آخر أولاً.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $result = app(ThemeUploadService::class)->delete($record);

                        Notification::make()
                            ->title($result['success'] ? 'تم حذف الثيم' : 'فشل الحذف')
                            ->body($result['message'])
                            ->color($result['success'] ? 'success' : 'danger')
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
