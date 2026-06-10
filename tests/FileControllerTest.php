<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Tests;

use Illuminate\Support\Facades\Storage;

class FileControllerTest extends TestCase
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
            'file-uploader.allow_list' => true,
            'file-uploader.allow_cleanup' => true,
            'file-uploader.allow_delete_all_files' => true,
            'file-uploader.soft_delete' => true,
            'file-uploader.trash_ttl_days' => 30,
        ], $overrides));
    }

    private function seedFile(string $disk, string $name = 'doc.txt', string $content = 'document body'): string
    {
        $hash = hash('sha256', $content);
        $storage = Storage::disk($disk);
        $storage->put('uploads/'.$name, $content);
        $storage->put('.meta/'.$hash.'.json', (string) json_encode([
            'id' => $hash,
            'name' => $name,
            'size' => strlen($content),
            'mime' => 'text/plain',
            'path' => 'uploads/'.$name,
            'disk' => $disk,
            'hash' => $hash,
            'createdAt' => '2026-01-01T00:00:00+00:00',
            'deletedAt' => null,
            'trashPath' => null,
            'url' => null,
            'userId' => null,
        ], JSON_PRETTY_PRINT));

        return $hash;
    }

    public function test_index_lists_files_when_allow_list_enabled(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk);
        $hash = $this->seedFile($disk);

        $this->getJson(route('file-uploader.files.index'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.files.0.id', $hash);
    }

    public function test_index_returns_empty_when_allow_list_disabled(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk, ['file-uploader.allow_list' => false]);
        $this->seedFile($disk);

        $this->getJson(route('file-uploader.files.index'))
            ->assertOk()
            ->assertJsonPath('data.files', []);
    }

    public function test_destroy_moves_to_trash_then_restore_brings_it_back(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk);
        $hash = $this->seedFile($disk);
        $storage = Storage::disk($disk);

        $this->deleteJson(route('file-uploader.files.destroy', ['id' => $hash]))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertTrue($storage->exists('.trash/doc.txt'));
        $this->assertFalse($storage->exists('uploads/doc.txt'));

        $this->postJson(route('file-uploader.files.restore', ['id' => $hash]))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertTrue($storage->exists('uploads/doc.txt'));
        $this->assertFalse($storage->exists('.trash/doc.txt'));
    }

    public function test_destroy_unknown_id_returns_not_found(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk);

        $this->deleteJson(route('file-uploader.files.destroy', ['id' => 'does-not-exist']))
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_cleanup_is_forbidden_when_disabled(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk, ['file-uploader.allow_cleanup' => false]);

        $this->deleteJson(route('file-uploader.trash.cleanup'))
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_cleanup_runs_when_enabled(): void
    {
        $disk = 'files';
        Storage::fake($disk);
        $this->configure($disk);

        $this->deleteJson(route('file-uploader.trash.cleanup'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 0);
    }
}
