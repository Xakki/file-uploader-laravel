<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Services;

use Xakki\FileUploader\FileUploader;
use Xakki\LaravelFileUploader\Support\WiresCore;

/**
 * Laravel-wired upload service. Construct with `new FileUpload(config('file-uploader'))`
 * or resolve from the container; behaviour lives in the framework-agnostic core.
 */
class FileUpload extends FileUploader
{
    use WiresCore;
}
