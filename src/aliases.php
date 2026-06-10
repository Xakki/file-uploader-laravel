<?php

declare(strict_types=1);
use Xakki\LaravelFileUploader\DTO\FileMetadata;

// Backward-compatibility alias: the metadata DTO moved to the framework-agnostic
// core (Xakki\FileUploader\Dto\FileMetadata) in v1.0. The old Laravel FQCN is kept
// as an alias so existing type-hints and `instanceof` checks keep working.
if (! class_exists(FileMetadata::class, false)) {
    class_alias(Xakki\FileUploader\Dto\FileMetadata::class, FileMetadata::class);
}
