<?php

namespace App\Filament\Resources\Themes\Pages;

use App\Actions\Themes\UploadThemeAction;
use App\Filament\Resources\Themes\ThemeResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Storage;

class CreateTheme extends CreateRecord
{
    protected static string $resource = ThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadTheme')
                ->label('رفع ثيم جديد')
                ->icon('heroicon-m-cloud-arrow-up')
                ->color(Color::Blue)
                ->modalHeading('رفع ثيم جديد')
                ->modalDescription('قم برفع ملف ZIP يحتوي على الثيم مع ملف theme.json والملفات المطلوبة')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('theme_zip')
                        ->label('ملف الثيم (ZIP)')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(50000) // 50MB
                        ->required()
                        ->disk('local')
                        ->directory('temp/themes'),
                ])
                ->action(function (array $data): void {
                    $uploadPath = Storage::disk('local')->path($data['theme_zip']);
                    
                    $uploadAction = new UploadThemeAction();
                    $result = $uploadAction->execute($uploadPath);
                    
                    if ($result['success']) {
                        // Delete the uploaded ZIP file
                        Storage::disk('local')->delete($data['theme_zip']);
                        
                        Notification::make()
                            ->title('تم الرفع بنجاح')
                            ->body($result['message'])
                            ->success()
                            ->send();
                        
                        $this->redirect(ThemeResource::getUrl('index'));
                    } else {
                        // Delete the uploaded ZIP file on failure
                        Storage::disk('local')->delete($data['theme_zip']);
                        
                        Notification::make()
                            ->title('فشل الرفع')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
