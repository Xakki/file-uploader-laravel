<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Xakki\FileUploader\FileUploader;
use Xakki\FileUploader\Protocol\ResponseFactory;

abstract class AbstractController extends Controller
{
    public function __construct(protected FileUploader $uploader) {}

    public function sendResponse(mixed $result, string $message, int $status = 200): JsonResponse
    {
        return response()->json(
            data: ResponseFactory::success($result, $message),
            status: $status,
            options: JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param  array<string,string[]>  $errorMessages
     */
    public function sendError(string $error, array $errorMessages = [], int $status = 404): JsonResponse
    {
        return response()->json(ResponseFactory::error($error, $errorMessages), $status);
    }
}
