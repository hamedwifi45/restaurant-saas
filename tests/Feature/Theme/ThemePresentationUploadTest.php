<?php

use App\Services\ThemeUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('theme uploads reject unapproved blade presentation files', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'theme_upload_');
    if ($zipPath === false) {
        throw new RuntimeException('Unable to allocate a temporary theme archive path.');
    }

    $temporaryPath = $zipPath;
    $zipPath .= '.zip';
    $archive = new ZipArchive;
    if ($archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        File::delete($temporaryPath);

        throw new RuntimeException('Unable to create the temporary theme archive.');
    }

    $archive->addFromString('theme.json', json_encode([
        'name' => 'Theme With Unsupported View',
        'slug' => 'theme-with-unsupported-view',
        'version' => '1.0.0',
        'default_settings' => [],
        'allowed_variables' => [],
    ], JSON_UNESCAPED_UNICODE));
    $archive->addFromString('layout.blade.php', '<html></html>');
    $archive->addFromString('pages/home.blade.php', '<div>Home</div>');
    $archive->addFromString('pages/unapproved.blade.php', '<div>Not an approved slot</div>');
    $archive->close();

    try {
        $result = app(ThemeUploadService::class)->upload($zipPath);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('قوالب العرض المعتمدة');
    } finally {
        File::delete([$zipPath, $temporaryPath]);
    }
});
