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

    // 🛡️ الامتدادات المسموح بها فقط داخل الثيم (تجاهل أو حظر أي شيء آخر)
    protected array $allowedExtensions = [
        'json',
        'css',
        'js',
        'png',
        'jpg',
        'jpeg',
        'webp',
        'svg',
        'gif',
        'woff',
        'woff2',
        'ttf',
        'eot'
    ];

    // 🛡️ الدوال البرمجية الممنوع ظهورها داخل ملفات Blade لمنع الـ Remote Code Execution
    protected array $forbiddenPhpFunctions = [
        'system',
        'exec',
        'shell_exec',
        'passthru',
        'eval',
        'base64_decode',
        'proc_open',
        'popen',
        'phpinfo',
        'assert',
        'preg_replace',
        'unserialize'
    ];

    protected array $requiredFiles = [
        'theme.json',
        'layout.blade.php',

        // صفحات المتجر والعروض والـ KDS
        'pages/home.blade.php',
        'pages/menu.blade.php',
        'pages/product.blade.php',
        'pages/offers.blade.php',
        'pages/kds-board.blade.php',

        // السلة والشراء
        'cart/cart.blade.php',
        'cart/checkout.blade.php',
        'cart/success.blade.php',

        // تتبع الطلبات والفواتير
        'orders/track.blade.php',
        'orders/track-form.blade.php',
        'orders/invoice.blade.php',

        // التقييمات
        'reviews/reviews.blade.php',
        'reviews/review-form.blade.php',

        // لوحة المندوبين (Drivers)
        'driver/dashboard.blade.php',
        'driver/orders.blade.php',
        'driver/tracking.blade.php',
    ];

    public function __construct()
    {
        $this->themesPath = resource_path('views/themes');
        File::ensureDirectoryExists($this->themesPath);
    }

    public function upload(string $zipPath, ?string $customFolderName = null): array
    {
        if (! File::exists($zipPath)) {
            return ['success' => false, 'message' => 'ملف ZIP غير موجود.'];
        }

        // 🛡️ 1. فحص توقيع الملف (Magic Bytes / MIME Type) وليس الامتداد فقط
        $mimeType = mime_content_type($zipPath);
        if (! in_array($mimeType, ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'], true)) {
            return ['success' => false, 'message' => 'تنبيه أمني: نوع الملف غير صالح أو تم التلاعب به.'];
        }

        $tempDir = storage_path('app/temp/themes/' . Str::random(32));
        File::ensureDirectoryExists($tempDir);

        try {
            $zip = new ZipArchive;

            if ($zip->open($zipPath) !== true) {
                return ['success' => false, 'message' => 'فشل في فتح الملف المضغوط.'];
            }

            // 🛡️ 2. فحص أمني قبل فك الضغط (منع Zip Slip & Zip Bomb)
            $securityCheck = $this->inspectZipArchive($zip);
            if (! $securityCheck['safe']) {
                $zip->close();
                $message = "رفض أمني حاسم - تم اكتشاف المشاكل التالية في الملف المضغوط:<br>• " . implode('<br>• ', $securityCheck['errors']);
                return ['success' => false, 'message' => $message];
            }

            // فك الضغط بأمان في مجلد مؤقت معزول
            $zip->extractTo($tempDir);
            $zip->close();

            // 🛡️ 3. المسح الجنائي للملفات المستخرجة (File Integrity & Malware Scanner)
            $scanResult = $this->scanExtractedFiles($tempDir);
            if (! $scanResult['safe']) {
                return ['success' => false, 'message' => $scanResult['message']];
            }

            $themeDir = $this->findThemeDirectory($tempDir);

            if (! $themeDir) {
                return ['success' => false, 'message' => 'لم يتم العثور على ملف theme.json داخل الـ ZIP.'];
            }

            $themeJson = $this->readThemeJson($themeDir);
            if (! is_array($themeJson)) {
                return ['success' => false, 'message' => 'ملف theme.json غير صالح أو غير موجود.'];
            }

            foreach (['name', 'slug', 'version'] as $field) {
                if (! isset($themeJson[$field]) || trim((string) $themeJson[$field]) === '') {
                    return ['success' => false, 'message' => "الحقل '{$field}' مفقود في theme.json."];
                }
            }

            if (! is_array($themeJson['allowed_variables'] ?? null)) {
                return ['success' => false, 'message' => 'حقل allowed_variables يجب أن يكون مصفوفة JSON صالحة.'];
            }

            // التحقق من وجود الملفات المطلوبة
            $missingFiles = [];
            foreach ($this->requiredFiles as $requiredFile) {
                if (! File::exists($themeDir . '/' . $requiredFile)) {
                    $missingFiles[] = $requiredFile;
                }
            }

            if ($missingFiles !== []) {
                return ['success' => false, 'message' => 'الملفات التالية مفقودة من الثيم:<br>• ' . implode('<br>• ', $missingFiles)];
            }

            $folderName = $this->resolveFolderName($customFolderName ?? $themeJson['slug'] ?? Str::slug($themeJson['name']));
            $targetDir = $this->themesPath . '/' . $folderName;

            if (File::isDirectory($targetDir)) {
                return ['success' => false, 'message' => "يوجد ثيم بالفعل بالمجلد '{$folderName}'."];
            }

            // نقل المجلد النظيف والمحصن إلى مجلد الثيمات النهائي
            File::moveDirectory($themeDir, $targetDir);

            $theme = Theme::updateOrCreate(
                ['folder_name' => $folderName],
                [
                    'name' => filter_var($themeJson['name'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'slug' => Str::slug($themeJson['slug']),
                    'author' => filter_var($themeJson['author'] ?? 'غير معروف', FILTER_SANITIZE_SPECIAL_CHARS),
                    'version' => filter_var($themeJson['version'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'description' => filter_var($themeJson['description'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
                    'preview_image' => $themeJson['preview_image'] ?? null,
                    'default_settings' => $themeJson['default_settings'] ?? [],
                    'allowed_variables' => $themeJson['allowed_variables'],
                    'is_active' => true,
                    'is_default' => ! Theme::where('is_default', true)->exists(),
                ]
            );

            return [
                'success' => true,
                'message' => 'تم رفع الثيم وفحصه أمنياً بنجاح.',
                'theme' => $theme,
                'folder_name' => $folderName,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أمني أثناء رفع الثيم: ' . $e->getMessage(),
            ];
        } finally {
            // تدمير المجلد المؤقت دائماً لمنع بقاء أي مخلفات
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * 🛡️ فحص هيكل الـ ZIP قبل فك الضغط (منع Zip Slip و Zip Bomb)
     */
    protected function inspectZipArchive(ZipArchive $zip): array
    {
        $maxFiles = 150; // أقصى عدد مسموح للملفات
        $maxTotalSize = 30 * 1024 * 1024; // أقصى حجم بعد فك الضغط (30MB)
        $totalSize = 0;
        $errors = [];

        if ($zip->numFiles > $maxFiles) {
            $errors[] = 'تنبيه أمني: الملف المضغوط يحتوي على عدد كبير جداً من الملفات (Zip Bomb Check).';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $stat['name'];

            // 1. منع Path Traversal (Zip Slip)
            if (str_contains($filename, '..') || str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
                $errors[] = "رفض أمني: تم اكتشاف محاولة اختراق مسارات في الملف ({$filename}).";
            }

            // 2. حظر حاسم لأي ملفات بلاملاحق خطيرة قبل الاستخراج
            $lowerName = strtolower($filename);
            if (str_ends_with($lowerName, '.php') && ! str_ends_with($lowerName, '.blade.php')) {
                $errors[] = "رفض أمني: الملف ({$filename}) غير مسموح به. يُمنع رفع ملفات PHP مباشرة.";
            }

            if (preg_match('/\.(phar|phtml|php3|php4|php5|php7|phps|exe|sh|bat|htaccess|env)$/i', $lowerName)) {
                $errors[] = "رفض أمني: يحتوي الملف المضغوط على ملفات تنفيذية خطيرة ({$filename}).";
            }

            $totalSize += $stat['size'];
        }

        if ($totalSize > $maxTotalSize) {
            $errors[] = 'تنبيه أمني: الحجم الكلي للملفات بعد فك الضغط يتجاوز الحد المسموح (30MB).';
        }

        if (! empty($errors)) {
            return ['safe' => false, 'errors' => $errors];
        }

        return ['safe' => true];
    }

    /**
     * 🛡️ الفحص الجنائي العميق للملفات المستخرجة (Deep Malware & Code Scan)
     */
    protected function scanExtractedFiles(string $tempDir): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $errors = [];

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            // استخراج المسار النسبي للملف مثل: driver/dashboard.blade.php
            $normalizedPath = str_replace('\\', '/', $file->getPathname());
            $normalizedTempDir = str_replace('\\', '/', $tempDir);
            $relativePath = ltrim(str_replace($normalizedTempDir, '', $normalizedPath), '/');

            $filename = $file->getFilename();
            $pathname = $file->getPathname();
            $lowerName = strtolower($filename);

            // أ) فحص امتداد الملف
            $isBlade = str_ends_with($lowerName, '.blade.php');
            $extension = strtolower($file->getExtension());

            if (! $isBlade && ! in_array($extension, $this->allowedExtensions, true)) {
                $errors[] = "الملف ({$relativePath}) يتضمن امتداداً غير مصرح به أمنياً (.{$extension}).";
            }

            // ب) فحص محتوى ملفات Blade لمنع تنفيذ أكواد PHP المباشرة
            if ($isBlade) {
                $content = File::get($pathname);

                // 1. حظر الوسوم العادية PHP <?php أو <?= أو @php
                if (preg_match('/<\?php/i', $content) || preg_match('/<\?=/i', $content) || preg_match('/@php/i', $content)) {
                    $errors[] = "الملف ({$relativePath}) يحتوي على كود PHP مباشر (<?php أو <?= أو @php).";
                }

                // 2. فحص وجود دالّات خطيرة حتى داخل أكواد Blade
                foreach ($this->forbiddenPhpFunctions as $forbiddenFunc) {
                    if (preg_match('/\b' . $forbiddenFunc . '\s*\(/i', $content)) {
                        $errors[] = "تم اكتشاف استدعاء لدالة برمجية خطيرة ({$forbiddenFunc}) داخل الملف ({$relativePath}).";
                    }
                }
            }
        }

        if (! empty($errors)) {
            return [
                'safe' => false,
                'errors' => $errors,
                'message' => "رفض أمني حاسم - تم اكتشاف الأخطاء التالية في ملفات الثيم:<br>• " . implode('<br>• ', $errors)
            ];
        }

        return ['safe' => true];
    }

    protected function findThemeDirectory(string $tempDir): ?string
    {
        if (File::exists($tempDir . '/theme.json')) {
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
        $path = $themeDir . '/theme.json';

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

    public function cloneTheme(Theme $theme, ?string $customName = null): array
    {
        $sourceDir = $this->themesPath . '/' . $theme->folder_name;

        if (! File::isDirectory($sourceDir)) {
            return ['success' => false, 'message' => 'مجلد الثيم الأصلي غير موجود.'];
        }

        $newName = trim((string) ($customName ?: $theme->name . ' نسخة'));
        $folderName = $this->resolveFolderName($newName === '' ? $theme->folder_name . '-copy' : $newName);
        $targetDir = $this->themesPath . '/' . $folderName;

        if (File::isDirectory($targetDir)) {
            return ['success' => false, 'message' => 'يوجد بالفعل ثيم بنفس الاسم.'];
        }

        File::copyDirectory($sourceDir, $targetDir);

        $jsonPath = $targetDir . '/theme.json';
        if (File::exists($jsonPath)) {
            $jsonContent = json_decode(File::get($jsonPath), true) ?? [];
            $jsonContent['name'] = $newName;
            $jsonContent['slug'] = Str::slug($newName) ?: $folderName;
            File::put($jsonPath, json_encode($jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $json = $this->readThemeJson($targetDir) ?? [];

        $newTheme = Theme::create([
            'name' => $newName,
            'slug' => Str::slug($newName) ?: $folderName,
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
        $themeDir = $this->themesPath . '/' . $theme->folder_name;

        if (File::isDirectory($themeDir)) {
            File::deleteDirectory($themeDir);
        }

        $theme->delete();

        return ['success' => true, 'message' => 'تم حذف الثيم بنجاح'];
    }

    public function exists(string $folderName): bool
    {
        return File::isDirectory($this->themesPath . '/' . $folderName)
            && File::exists($this->themesPath . '/' . $folderName . '/theme.json');
    }
}