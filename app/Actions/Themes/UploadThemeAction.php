<?php

namespace App\Actions\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class UploadThemeAction
{
    /**
     * Required fields in theme.json
     */
    protected array $requiredFields = [
        'name',
        'slug',
        'version',
        'default_settings',
        'allowed_variables',
    ];

    /**
     * Required structure for default_settings
     */
    protected array $requiredDefaultSettings = [
        'primary_color',
        'secondary_color',
        'background_color',
    ];

    /**
     * Execute the theme upload action
     *
     * @param string $themeZipPath Path to the uploaded ZIP file
     * @return array ['success' => bool, 'message' => string, 'theme' => Theme|null]
     */
    public function execute(string $themeZipPath): array
    {
        try {
            // Validate ZIP file
            if (!file_exists($themeZipPath)) {
                return [
                    'success' => false,
                    'message' => 'ملف الثيم غير موجود',
                    'theme' => null,
                ];
            }

            // Extract ZIP to temporary directory
            $tempDir = storage_path('app/temp/themes/' . uniqid());
            
            if (!$this->extractZip($themeZipPath, $tempDir)) {
                return [
                    'success' => false,
                    'message' => 'فشل فك ضغط ملف الثيم',
                    'theme' => null,
                ];
            }

            // Find theme folder (should contain theme.json)
            $themeFolder = $this->findThemeFolder($tempDir);
            
            if (!$themeFolder) {
                $this->cleanupTempDir($tempDir);
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على مجلد الثيم أو ملف theme.json',
                    'theme' => null,
                ];
            }

            // Validate theme.json
            $validation = $this->validateThemeJson($themeFolder);
            
            if (!$validation['valid']) {
                $this->cleanupTempDir($tempDir);
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'theme' => null,
                ];
            }

            // Parse theme.json
            $themeData = json_decode(file_get_contents($themeFolder . '/theme.json'), true);

            // Check if theme already exists
            $existingTheme = Theme::where('slug', $themeData['slug'])->first();
            
            if ($existingTheme) {
                $this->cleanupTempDir($tempDir);
                return [
                    'success' => false,
                    'message' => 'ثيم بنفس الرابط المختصر موجود مسبقاً',
                    'theme' => null,
                ];
            }

            // Move theme folder to final destination
            $themesBasePath = resource_path('views/themes');
            $finalThemePath = $themesBasePath . '/' . $themeData['slug'];

            if (File::exists($finalThemePath)) {
                $this->cleanupTempDir($tempDir);
                return [
                    'success' => false,
                    'message' => 'مجلد الثيم موجود مسبقاً',
                    'theme' => null,
                ];
            }

            // Create themes directory if not exists
            File::ensureDirectoryExists($themesBasePath);

            // Move theme folder
            File::move($themeFolder, $finalThemePath);

            // Handle preview image if exists
            $previewImagePath = null;
            $possiblePreviewPaths = [
                $finalThemePath . '/preview.png',
                $finalThemePath . '/preview.jpg',
                $finalThemePath . '/preview.jpeg',
                $finalThemePath . '/screenshot.png',
                $finalThemePath . '/screenshot.jpg',
            ];

            foreach ($possiblePreviewPaths as $previewPath) {
                if (File::exists($previewPath)) {
                    $previewFileName = basename($previewPath);
                    $previewDest = storage_path('app/public/themes/previews/' . $themeData['slug'] . '_' . $previewFileName);
                    
                    File::ensureDirectoryExists(dirname($previewDest));
                    File::copy($previewPath, $previewDest);
                    
                    $previewImagePath = 'themes/previews/' . $themeData['slug'] . '_' . $previewFileName;
                    break;
                }
            }

            // Create theme record in database
            $theme = Theme::create([
                'name' => $themeData['name'],
                'slug' => $themeData['slug'],
                'author' => $themeData['author'] ?? 'Unknown',
                'version' => $themeData['version'],
                'description' => $themeData['description'] ?? '',
                'folder_name' => $themeData['slug'],
                'preview_image' => $previewImagePath,
                'default_settings' => $themeData['default_settings'],
                'allowed_variables' => $themeData['allowed_variables'],
                'is_active' => false, // Default to inactive until admin activates
                'is_default' => false,
            ]);

            // Cleanup temp directory
            $this->cleanupTempDir($tempDir);

            return [
                'success' => true,
                'message' => 'تم رفع الثيم بنجاح: ' . $themeData['name'],
                'theme' => $theme,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage(),
                'theme' => null,
            ];
        }
    }

    /**
     * Extract ZIP file to destination
     */
    protected function extractZip(string $zipPath, string $destination): bool
    {
        try {
            $zip = new \ZipArchive();
            
            if ($zip->open($zipPath) !== true) {
                return false;
            }

            File::ensureDirectoryExists($destination);
            
            if (!$zip->extractTo($destination)) {
                $zip->close();
                return false;
            }

            $zip->close();
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find the theme folder containing theme.json
     */
    protected function findThemeFolder(string $baseDir): string|false
    {
        // Check if theme.json is in the root of extracted folder
        if (File::exists($baseDir . '/theme.json')) {
            return $baseDir;
        }

        // Look for subdirectories that might contain theme.json
        $directories = File::directories($baseDir);
        
        foreach ($directories as $dir) {
            if (File::exists($dir . '/theme.json')) {
                return $dir;
            }
            
            // Recursively search one level deeper
            $subDirs = File::directories($dir);
            foreach ($subDirs as $subDir) {
                if (File::exists($subDir . '/theme.json')) {
                    return $subDir;
                }
            }
        }

        return false;
    }

    /**
     * Validate theme.json structure and content
     */
    protected function validateThemeJson(string $themeFolder): array
    {
        $themeJsonPath = $themeFolder . '/theme.json';

        // Check if theme.json exists
        if (!File::exists($themeJsonPath)) {
            return [
                'valid' => false,
                'message' => 'ملف theme.json غير موجود في الثيم',
            ];
        }

        // Parse JSON
        $content = File::get($themeJsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'message' => 'ملف theme.json يحتوي على صيغة JSON غير صحيحة',
            ];
        }

        // Validate required fields
        foreach ($this->requiredFields as $field) {
            if (!isset($data[$field])) {
                return [
                    'valid' => false,
                    'message' => "الحقل '{$field}' مطلوب في theme.json",
                ];
            }
        }

        // Validate slug format
        if (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            return [
                'valid' => false,
                'message' => 'الرابط المختصر (slug) يجب أن يحتوي على أحرف صغيرة وأرقام وشرطات فقط',
            ];
        }

        // Validate version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $data['version'])) {
            return [
                'valid' => false,
                'message' => 'إصدار الثيم يجب أن يكون بصيغة X.Y.Z (مثال: 1.0.0)',
            ];
        }

        // Validate default_settings is an array
        if (!is_array($data['default_settings'])) {
            return [
                'valid' => false,
                'message' => 'default_settings يجب أن يكون كائناً (object)',
            ];
        }

        // Validate allowed_variables is an array
        if (!is_array($data['allowed_variables'])) {
            return [
                'valid' => false,
                'message' => 'allowed_variables يجب أن يكون كائناً (object)',
            ];
        }

        // Validate required default settings
        foreach ($this->requiredDefaultSettings as $setting) {
            if (!isset($data['default_settings'][$setting])) {
                return [
                    'valid' => false,
                    'message' => "الإعداد الافتراضي '{$setting}' مطلوب في default_settings",
                ];
            }
        }

        // Validate each allowed variable has type and label
        foreach ($data['allowed_variables'] as $key => $variable) {
            if (!isset($variable['type'])) {
                return [
                    'valid' => false,
                    'message' => "المتغير المسموح '{$key}' يجب أن يحتوي على حقل 'type'",
                ];
            }
            
            if (!isset($variable['label'])) {
                return [
                    'valid' => false,
                    'message' => "المتغير المسموح '{$key}' يجب أن يحتوي على حقل 'label'",
                ];
            }
        }

        // Check for required theme files
        $requiredFiles = ['layout.blade.php'];
        
        foreach ($requiredFiles as $file) {
            if (!File::exists($themeFolder . '/' . $file)) {
                return [
                    'valid' => false,
                    'message' => "الملف '{$file}' مطلوب في مجلد الثيم",
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'الثيم صالح',
        ];
    }

    /**
     * Cleanup temporary directory
     */
    protected function cleanupTempDir(string $tempDir): void
    {
        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }
    }
}
