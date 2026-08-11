<?php

namespace App\Filament\Resources\Themes\Pages;

use App\Filament\Resources\Themes\ThemeResource;
use App\Services\ThemeUploadService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Storage;

class UploadTheme extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ThemeResource::class;

    protected static string $view = 'filament.resources.themes.pages.upload-theme';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('theme_zip')
                    ->label('ملف الثيم (ZIP)')
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                    ->maxSize(50 * 1024) // 50 MB
                    ->required()
                    ->directory('temp/themes')
                    ->storeFiles(true)
                    ->visibility('private')
                    ->preserveFilenames()
                    ->helperText('يجب أن يحتوي الملف على theme.json و layout.blade.php والملفات الأساسية الأخرى'),
            ])
            ->statePath('data');
    }

    public function uploadTheme(): void
    {
        $formData = $this->form->getState();
        
        if (!isset($formData['theme_zip'])) {
            Notification::make()
                ->title('خطأ')
                ->body('يرجى اختيار ملف ZIP للثيم')
                ->danger()
                ->send();
            
            return;
        }

        $zipPath = Storage::disk('private')->path($formData['theme_zip']);

        if (!file_exists($zipPath)) {
            Notification::make()
                ->title('خطأ')
                ->body('الملف المحدد غير موجود')
                ->danger()
                ->send();
            
            return;
        }

        $uploadService = app(ThemeUploadService::class);
        $result = $uploadService->upload($zipPath);

        if ($result['success']) {
            // حذف الملف المؤقت
            Storage::disk('private')->delete($formData['theme_zip']);

            Notification::make()
                ->title('نجاح')
                ->body('تم رفع الثيم "' . $result['theme']->name . '" بنجاح')
                ->success()
                ->send();

            $this->redirect(ThemeResource::getUrl());
        } else {
            // حذف الملف المؤقت في حالة الفشل
            Storage::disk('private')->delete($formData['theme_zip']);

            Notification::make()
                ->title('فشل رفع الثيم')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('رفع الثيم')
                ->action('uploadTheme')
                ->color('success')
                ->icon('heroicon-o-cloud-arrow-up'),
        ];
    }
}
