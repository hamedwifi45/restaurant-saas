<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadTheme extends Component
{
    use WithFileUploads;

    public $themeZip;
    public $themeName = '';
    public $themeAuthor = '';
    public $themeVersion = '1.0.0';
    public $themeDescription = '';
    
    public $uploadProgress = 0;
    public $isUploading = false;
    public $validationErrors = [];
    public $validationSuccess = false;
    public $themePreview = null;
    public $themeJsonData = null;
    
    public $showPreviewModal = false;
    public $currentStep = 1;
    public $totalSteps = 3;

    protected $rules = [
        'themeZip' => 'required|mimes:zip|max:51200',
        'themeName' => 'required|string|max:255',
        'themeAuthor' => 'nullable|string|max:255',
        'themeVersion' => 'nullable|string|max:50',
        'themeDescription' => 'nullable|string|max:1000',
    ];

    public function updatedThemeZip()
    {
        $this->resetValidation();
        $this->validationErrors = [];
        $this->validationSuccess = false;
        $this->themeJsonData = null;
        
        if ($this->themeZip) {
            $this->validateThemeZip();
        }
    }

    public function validateThemeZip()
    {
        try {
            $tempPath = $this->themeZip->getRealPath();
            $zip = new \ZipArchive();
            
            if ($zip->open($tempPath) !== true) {
                $this->validationErrors[] = 'الملف المرفوع ليس ملف ZIP صالح أو تالف';
                return false;
            }

            // التحقق من وجود theme.json
            $themeJsonIndex = $zip->locateName('theme.json');
            if ($themeJsonIndex === false) {
                $this->validationErrors[] = 'الثيم يجب أن يحتوي على ملف theme.json في الجذر';
                $zip->close();
                return false;
            }

            // قراءة محتوى theme.json
            $themeJsonContent = $zip->getFromIndex($themeJsonIndex);
            $themeJsonData = json_decode($themeJsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->validationErrors[] = 'ملف theme.json يحتوي على صيغة JSON غير صحيحة';
                $zip->close();
                return false;
            }

            // التحقق من الحقول المطلوبة في theme.json
            $requiredFields = ['name', 'author', 'version'];
            foreach ($requiredFields as $field) {
                if (!isset($themeJsonData[$field])) {
                    $this->validationErrors[] = "ملف theme.json يجب أن يحتوي على الحقل: {$field}";
                    $zip->close();
                    return false;
                }
            }

            // التحقق من وجود default_settings
            if (!isset($themeJsonData['default_settings']) || !is_array($themeJsonData['default_settings'])) {
                $this->validationErrors[] = 'ملف theme.json يجب أن يحتوي على default_settings كمصفوفة';
                $zip->close();
                return false;
            }

            // التحقق من وجود allowed_variables
            if (!isset($themeJsonData['allowed_variables']) || !is_array($themeJsonData['allowed_variables'])) {
                $this->validationErrors[] = 'ملف theme.json يجب أن يحتوي على allowed_variables كمصفوفة';
                $zip->close();
                return false;
            }

            // التحقق من وجود layout.blade.php
            $layoutIndex = $zip->locateName('layout.blade.php');
            if ($layoutIndex === false) {
                $this->validationErrors[] = 'الثيم يجب أن يحتوي على ملف layout.blade.php';
                $zip->close();
                return false;
            }

            // استخراج اسم المجلد من slug
            $slug = Str::slug($themeJsonData['name']);
            
            // التحقق من عدم وجود مجلدات فرعية غير مرغوبة
            $fileCount = $zip->numFiles;
            for ($i = 0; $i < $fileCount; $i++) {
                $fileInfo = $zip->statIndex($i);
                $fileName = $fileInfo['name'];
                
                // التحقق من أن الملفات ليست في مجلدات محظورة
                if (strpos($fileName, '../') !== false || strpos($fileName, './') === 0) {
                    $this->validationErrors[] = 'الثيم يحتوي على مسارات غير آمنة';
                    $zip->close();
                    return false;
                }
            }

            // حفظ البيانات للعرض
            $this->themeJsonData = $themeJsonData;
            $this->themeName = $themeJsonData['name'] ?? '';
            $this->themeAuthor = $themeJsonData['author'] ?? '';
            $this->themeVersion = $themeJsonData['version'] ?? '1.0.0';
            $this->themeDescription = $themeJsonData['description'] ?? '';
            
            // البحث عن صورة معاينة
            $previewIndex = $zip->locateName('preview.png');
            if ($previewIndex === false) {
                $previewIndex = $zip->locateName('preview.jpg');
            }
            if ($previewIndex === false) {
                $previewIndex = $zip->locateName('screenshot.png');
            }
            
            if ($previewIndex !== false) {
                $this->themePreview = 'data:image/png;base64,' . base64_encode($zip->getFromIndex($previewIndex));
            }

            $zip->close();
            $this->validationSuccess = true;
            return true;

        } catch (\Exception $e) {
            $this->validationErrors[] = 'حدث خطأ أثناء التحقق من الملف: ' . $e->getMessage();
            return false;
        }
    }

    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {
            if ($this->currentStep === 1 && !$this->validationSuccess) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'يرجى التحقق من صحة الثيم أولاً'
                ]);
                return;
            }
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function uploadTheme()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $slug = Str::slug($this->themeJsonData['name']);
            
            // التحقق من عدم وجود ثيم بنفس الاسم
            if (Theme::where('slug', $slug)->exists()) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'ثيم بهذا الاسم موجود مسبقاً'
                ]);
                DB::rollBack();
                return;
            }

            // إنشاء مجلد الثيم
            $themePath = resource_path("views/themes/{$slug}");
            
            if (File::exists($themePath)) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'مجلد الثيم موجود مسبقاً'
                ]);
                DB::rollBack();
                return;
            }

            File::makeDirectory($themePath, 0755, true);

            // فك ضغط الملفات
            $tempPath = $this->themeZip->getRealPath();
            $zip = new \ZipArchive();
            
            if ($zip->open($tempPath) === true) {
                $zip->extractTo($themePath);
                $zip->close();
            } else {
                throw new \Exception('فشل في فك ضغط الملف');
            }

            // حفظ صورة المعاينة إن وجدت
            $previewImagePath = null;
            if ($this->themeZip) {
                $zip->open($tempPath);
                $previewIndex = $zip->locateName('preview.png');
                if ($previewIndex === false) {
                    $previewIndex = $zip->locateName('preview.jpg');
                }
                if ($previewIndex !== false) {
                    $previewContent = $zip->getFromIndex($previewIndex);
                    $extension = pathinfo($zip->getNameIndex($previewIndex), PATHINFO_EXTENSION);
                    $previewImagePath = "themes/{$slug}/preview.{$extension}";
                    File::put(public_path("storage/{$previewImagePath}"), $previewContent);
                }
                $zip->close();
            }

            // إنشاء سجل الثيم في قاعدة البيانات
            $theme = Theme::create([
                'name' => $this->themeJsonData['name'],
                'slug' => $slug,
                'author' => $this->themeJsonData['author'] ?? 'غير معروف',
                'version' => $this->themeJsonData['version'] ?? '1.0.0',
                'description' => $this->themeJsonData['description'] ?? '',
                'folder_name' => $slug,
                'preview_image' => $previewImagePath,
                'default_settings' => $this->themeJsonData['default_settings'] ?? [],
                'allowed_variables' => $this->themeJsonData['allowed_variables'] ?? [],
                'is_active' => true,
                'is_default' => false,
            ]);

            DB::commit();

            $this->reset(['themeZip', 'themeName', 'themeAuthor', 'themeVersion', 'themeDescription']);
            $this->validationSuccess = false;
            $this->themeJsonData = null;
            $this->themePreview = null;
            $this->currentStep = 1;
            $this->showPreviewModal = false;

            $this->dispatch('alert', [
                'type' => 'success',
                'message' => 'تم رفع الثيم بنجاح: ' . $theme->name
            ]);

            $this->dispatch('theme-uploaded');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // تنظيف المجلد في حالة الفشل
            if (isset($themePath) && File::exists($themePath)) {
                File::deleteDirectory($themePath);
            }

            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'فشل رفع الثيم: ' . $e->getMessage()
            ]);
        }
    }

    public function cancelUpload()
    {
        $this->reset(['themeZip', 'themeName', 'themeAuthor', 'themeVersion', 'themeDescription']);
        $this->validationSuccess = false;
        $this->validationErrors = [];
        $this->themeJsonData = null;
        $this->themePreview = null;
        $this->currentStep = 1;
        $this->showPreviewModal = false;
    }

    public function render()
    {
        return view('livewire.admin.themes.upload-theme');
    }
}
