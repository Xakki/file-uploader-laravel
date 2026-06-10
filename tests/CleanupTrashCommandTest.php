<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class CleanupTrashCommandTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function configure(string $disk, array $overrides = []): void
    {
        config(array_merge([
            'file-uploader.disk' => $disk,
            'file-uploader.directory' => 'uploads',
            'file-uploader.metadata_directory' => '.meta',
            'file-uploader.trash_directory' => '.trash',
            'file-uploader.temporary_directory' => '.chunks',
            'file-uploader.trash_ttl_days' => 30,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function trashedMeta(string $hash, string $disk, int $size, string $deletedAt): array
    {
        return [
            'id' => $hash,
            'name' => 'old.txt',
            'size' => $size,
            'mime' => 'text/plain',
            'path' => null,
            'disk' => $disk,
            'hash' => $hash,
            'createdAt' => '2020-01-01T00:00:00+00:00',
            'deletedAt' => $deletedAt,
            'trashPath' => '.trash/old.txt',
            'url' => null,
            'userId' => null,
        ];
    }

    public function test_cleanup_removes_expired_trashed_files(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk);

        $storage = Storage::disk($disk);
        $content = 'trashed content';
        $hash = hash('sha256', $content);
        $storage->put('.trash/old.txt', $content);
        $storage->put('.meta/'.$hash.'.json', (string) json_encode(
            $this->trashedMeta($hash, $disk, strlen($content), '2020-01-02T00:00:00+00:00'),
            JSON_PRETTY_PRINT,
        ));

        $exitCode = Artisan::call('file-uploader:cleanup');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Removed 1', Artisan::output());
        $this->assertFalse($storage->exists('.trash/old.txt'));
        $this->assertFalse($storage->exists('.meta/'.$hash.'.json'));
    }

    public function test_cleanup_keeps_unexpired_trashed_files(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk);

        $storage = Storage::disk($disk);
        $content = 'recent content';
        $hash = hash('sha256', $content);
        $storage->put('.trash/old.txt', $content);
        $storage->put('.meta/'.$hash.'.json', (string) json_encode(
            $this->trashedMeta($hash, $disk, strlen($content), now()->toIso8601String()),
            JSON_PRETTY_PRINT,
        ));

        $exitCode = Artisan::call('file-uploader:cleanup');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Removed 0', Artisan::output());
        $this->assertTrue($storage->exists('.trash/old.txt'));
        $this->assertTrue($storage->exists('.meta/'.$hash.'.json'));
    }
}
