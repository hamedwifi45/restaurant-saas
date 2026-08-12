<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ThemeUploadService
{
    protected string $themesPath;

    protected array $requiredFiles = [
        'theme.json',
        'layout.blade.php',
        'pages/home.blade.php',
        'pages/menu.blade.php',
        'pages/product.blade.php',
        'cart/cart.blade.php',
        'cart/checkout.blade.php',
        'cart/success.blade.php',
        'orders/track.blade.php',
        'orders/track-form.blade.php',
        'orders/invoice.blade.php',
        'reviews/reviews.blade.php',
        'reviews/review-form.blade.php',
    ];

    public function __construct()
    {
        $this->themesPath = resource_path('views/themes');
        File::ensureDirectoryExists($this->themesPath);
    }

    /**
     * رفع وفك ضغط ثيم جديد
     */
    public function upload(string $zipPath, ?string $customFolderName = null): array
    {
        if (! File::exists($zipPath)) {
            return ['success' => false, 'message' => 'ملف ZIP غير موجود.'];
        }

        $extension = strtolower(pathinfo($zipPath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['zip', 'gz'], true) && ! str_ends_with($zipPath, '.zip')) {
            return ['success' => false, 'message' => 'نوع الملف غير مدعوم. يجب أن يكون ZIP صالحاً.'];
        }

        $tempDir = storage_path('app/temp/themes/'.Str::random(16));
        File::ensureDirectoryExists($tempDir);

        try {
            $zip = new ZipArchive;

            if ($zip->open($zipPath) !== true) {
                return ['success' => false, 'message' => 'فشل في فك ضغط الملف. تأكد من أن الملف ZIP صالح.'];
            }

            $zip->extractTo($tempDir);
            $zip->close();

            $themeDir = $this->findThemeDirectory($tempDir);

            if (! $themeDir) {
                return ['success' => false, 'message' => 'لم يتم العثور على ملف theme.json داخل الـ ZIP. تأكد من أن المجلد الرئيسي للثيم يحتوي على هذا الملف.'];
            }

            $themeJson = $this->readThemeJson($themeDir);
            if (! is_array($themeJson)) {
                return ['success' => false, 'message' => 'ملف theme.json غير صالح أو غير موجود.'];
            }

            foreach (['name', 'slug', 'version'] as $field) {
                if (! isset($themeJson[$field]) || trim((string) $themeJson[$field]) === '') {
                    return ['success' => false, 'message' => "الحقل '{$field}' مفقود في theme.json أو فارغ."];
                }
            }

            if (! is_array($themeJson['default_settings'] ?? null)) {
                return ['success' => false, 'message' => 'حقل default_settings يجب أن يكون مصفوفة JSON صالحة.'];
            }

            if (! is_array($themeJson['allowed_variables'] ?? null)) {
                return ['success' => false, 'message' => 'حقل allowed_variables يجب أن يكون مصفوفة JSON صالحة.'];
            }

            $missingFiles = [];
            foreach ($this->requiredFiles as $requiredFile) {
                if (! File::exists($themeDir.'/'.$requiredFile)) {
                    $missingFiles[] = $requiredFile;
                }
            }

            if ($missingFiles !== []) {
                return ['success' => false, 'message' => 'الملفات التالية مفقودة من الثيم: '.implode(', ', $missingFiles)];
            }

            $folderName = $this->resolveFolderName($customFolderName ?? $themeJson['slug'] ?? Str::slug($themeJson['name']));
            $targetDir = $this->themesPath.'/'.$folderName;

            if (File::isDirectory($targetDir)) {
                return ['success' => false, 'message' => "يوجد ثيم بالفعل بالمجلد '{$folderName}'. اختر اسماً مميزاً أو احذف الثيم الحالي أولاً."];
            }

            File::moveDirectory($themeDir, $targetDir);

            $theme = Theme::updateOrCreate(
                ['folder_name' => $folderName],
                [
                    'name' => $themeJson['name'],
                    'slug' => $themeJson['slug'],
                    'author' => $themeJson['author'] ?? 'غير معروف',
                    'version' => $themeJson['version'],
                    'description' => $themeJson['description'] ?? '',
                    'preview_image' => $themeJson['preview_image'] ?? null,
                    'default_settings' => $themeJson['default_settings'],
                    'allowed_variables' => $themeJson['allowed_variables'],
                    'is_active' => true,
                    'is_default' => ! Theme::where('is_default', true)->exists(),
                ]
            );

            return [
                'success' => true,
                'message' => 'تم رفع الثيم بنجاح وتثبيته في مجلد themes/.'.$folderName,
                'theme' => $theme,
                'folder_name' => $folderName,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع الثيم: '.$e->getMessage(),
            ];
        } finally {
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * البحث عن مجلد الثيم داخل المجلد المؤقت
     */
    protected function findThemeDirectory(string $tempDir): ?string
    {
        if (File::exists($tempDir.'/theme.json')) {
            return $tempDir;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getFilename()) === 'theme.json') {
                return dirname($file->getPathname());
            }
        }

        return null;
    }

    protected function readThemeJson(string $themeDir): ?array
    {
        $path = $themeDir.'/theme.json';

        if (! File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $decoded = json_decode((string) $content, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function resolveFolderName(string $name): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            throw new \RuntimeException('اسم المجلد غير صالح بعد التنظيف.');
        }

        return $slug;
    }

    /**
     * نسخ ثيم موجود
     */
    public function cloneTheme(Theme $theme, ?string $customName = null, ?string $customSlug = null): array
    {
        $sourceDir = $this->themesPath.'/'.$theme->folder_name;

        if (! File::isDirectory($sourceDir)) {
            return ['success' => false, 'message' => 'مجلد الثيم الأصلي غير موجود.'];
        }

        $newName = trim((string) ($customName ?: $theme->name.' نسخة'));
        
        // استخدام الـ slug المخصص إذا تم تقديمه
        if ($customSlug) {
            $folderName = Str::slug($customSlug);
        } else {
            $folderName = $this->resolveFolderName($newName === '' ? $theme->folder_name.'-copy' : $newName);
        }
        
        $targetDir = $this->themesPath.'/'.$folderName;

        if (File::isDirectory($targetDir)) {
            return ['success' => false, 'message' => 'يوجد بالفعل ثيم بنفس الاسم أو slug.'];
        }

        File::copyDirectory($sourceDir, $targetDir);

        $json = $theme->loadThemeJson() ?? [];
        $newTheme = Theme::create([
            'name' => $newName,
            'slug' => $customSlug ?: Str::slug($newName) ?: $folderName,
            'author' => $json['author'] ?? $theme->author,
            'version' => $json['version'] ?? $theme->version,
            'description' => $json['description'] ?? $theme->description,
            'folder_name' => $folderName,
            'preview_image' => $theme->preview_image,
            'default_settings' => $json['default_settings'] ?? $theme->default_settings,
            'allowed_variables' => $json['allowed_variables'] ?? $theme->allowed_variables,
            'is_active' => false,
            'is_default' => false,
        ]);

        return ['success' => true, 'message' => 'تم نسخ الثيم بنجاح.', 'theme' => $newTheme];
    }

    public function delete(Theme $theme): array
    {
        $themeDir = $this->themesPath.'/'.$theme->folder_name;

        if (File::isDirectory($themeDir)) {
            File::deleteDirectory($themeDir);
        }

        $theme->delete();

        return ['success' => true, 'message' => 'تم حذف الثيم بنجاح'];
    }

    /**
     * التحقق من وجود ثيم معين
     */
    public function exists(string $folderName): bool
    {
        return File::isDirectory($this->themesPath.'/'.$folderName)
            && File::exists($this->themesPath.'/'.$folderName.'/theme.json');
    }
}
