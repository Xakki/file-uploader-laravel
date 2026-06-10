<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Runs the shared Upload Protocol v1 fixtures against the Laravel server. The fixtures ship
 * inside the core package (`xakki/file-uploader`); together with the Symfony and JS suites
 * running the same fixtures, this is the cross-language anti-drift gate
 * (protocol/fixtures/README.md). In particular it proves the Laravel FormRequest emits the same
 * `errors`-keyed-by-field shape as the core ChunkValidator used by the other bindings.
 */
class ConformanceTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__.'/../vendor/xakki/file-uploader/protocol/fixtures';

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function fixtures(): iterable
    {
        foreach (glob(self::FIXTURES_DIR.'/*.json') ?: [] as $path) {
            /** @var array<string, mixed> $fixture */
            $fixture = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            yield $fixture['name'] => [$fixture];
        }
    }

    /**
     * @param  array<string, mixed>  $fixture
     */
    #[DataProvider('fixtures')]
    public function test_fixture(array $fixture): void
    {
        Storage::fake('files');
        config([
            'file-uploader.disk' => 'files',
            'file-uploader.chunk_size' => 4 * 1024 * 1024,
            'file-uploader.max_size' => 200 * 1024 * 1024,
            'file-uploader.allowed_extensions' => [],
        ]);

        $fillByte = $fixture['file']['fillByte'];

        foreach ($fixture['requests'] as $request) {
            $fields = $request['fields'];
            $chunk = UploadedFile::fake()->createWithContent(
                (string) ($fields['fileName'] ?? 'chunk'),
                str_repeat(chr($fillByte), $request['chunkBytes']),
            );

            $response = $this->post(route('file-uploader.chunks.store'), $fields + ['fileChunk' => $chunk]);
            $expect = $request['expect'];

            $response->assertStatus($expect['status']);
            $this->assertSame($expect['success'], $response->json('success'), $fixture['name'].': success');

            if (isset($expect['data'])) {
                $this->assertSubset($expect['data'], (array) $response->json('data'), $fixture['name'].': data.');
            }

            if (isset($expect['errors'])) {
                $errors = (array) $response->json('errors');
                foreach ($expect['errors'] as $field) {
                    $this->assertArrayHasKey($field, $errors, $fixture['name'].": errors[$field]");
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $actual
     */
    private function assertSubset(array $expected, array $actual, string $path): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual, $path.$key);
            if (is_array($value)) {
                $this->assertIsArray($actual[$key], $path.$key);
                $this->assertSubset($value, $actual[$key], $path.$key.'.');
            } else {
                $this->assertSame($value, $actual[$key], $path.$key);
            }
        }
    }
}
