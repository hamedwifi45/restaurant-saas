<?php

namespace App\Filament\Resources\Themes\Pages;

use App\Filament\Resources\Themes\ThemeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListThemes extends ListRecords
{
    protected static string $resource = ThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_theme')
                ->label('رفع ثيم ZIP')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
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
                    $result = app(\App\Services\ThemeUploadService::class)->upload($path);

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

            CreateAction::make()
                ->label('إنشاء يدوي'),
        ];
    }
}
