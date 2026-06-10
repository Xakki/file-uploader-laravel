<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Storage;

use Illuminate\Filesystem\FilesystemAdapter;
use Xakki\FileUploader\Contracts\Storage;

/**
 * Core Storage implemented over a Laravel disk. Near pass-through: the Illuminate
 * FilesystemAdapter already exposes these operations. URL resolution preserves the
 * legacy `public_url_resolver` config hook.
 */
final class LaravelStorage implements Storage
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly FilesystemAdapter $disk,
        private readonly array $config,
    ) {}

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    public function read(string $path): string
    {
        return (string) $this->disk->get($path);
    }

    public function write(string $path, string $contents): bool
    {
        return (bool) $this->disk->put($path, $contents);
    }

    public function readStream(string $path)
    {
        return $this->disk->readStream($path);
    }

    public function writeStream(string $path, $resource): bool
    {
        return $this->disk->writeStream($path, $resource);
    }

    public function delete(string $path): bool
    {
        return $this->disk->delete($path);
    }

    public function deleteDirectory(string $path): bool
    {
        return $this->disk->deleteDirectory($path);
    }

    public function move(string $from, string $to): bool
    {
        return $this->disk->move($from, $to);
    }

    public function makeDirectory(string $path): bool
    {
        return $this->disk->makeDirectory($path);
    }

    public function files(string $directory): array
    {
        return $this->disk->files($directory);
    }

    public function allFiles(string $directory): array
    {
        return $this->disk->allFiles($directory);
    }

    public function size(string $path): int
    {
        return (int) $this->disk->size($path);
    }

    public function mimeType(string $path): string
    {
        return (string) $this->disk->mimeType($path);
    }

    public function url(string $path): ?string
    {
        $resolver = $this->config['public_url_resolver'] ?? null;
        if ($resolver && is_callable($resolver)) {
            return $resolver($path, $this->disk);
        }

        return $this->disk->url($path);
    }
}
