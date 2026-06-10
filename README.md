# xakki/laravel-file-uploader

Chunked file uploader for **Laravel 10–12** speaking
**[Upload Protocol v1](https://github.com/Xakki/file-uploader/blob/main/protocol/SPEC.md)**,
with a Drag & Drop JS widget. Thin binding over the framework-agnostic
[`xakki/file-uploader`](https://github.com/Xakki/file-uploader) core.

> **Upgrading from 0.2?** See [MIGRATION.md](MIGRATION.md). The public Laravel API (facades,
> routes, config, widget) is preserved; only the internals moved to the core.

```bash
composer require xakki/laravel-file-uploader
```

The service provider is auto-discovered. It registers the routes and, on `package:discover`,
publishes the config, the widget asset and the translations automatically. To (re)publish manually:

```bash
php artisan vendor:publish --tag=file-uploader-config
php artisan vendor:publish --tag=file-uploader-assets
php artisan vendor:publish --tag=file-uploader-translations
```

## Configure

`config/file-uploader.php` (key options):

```php
'disk' => env('FILE_UPLOADER_DISK', 'public'),   // any league/flysystem disk
'directory' => '/',
'chunk_size' => 1024 * 1024,                      // 1 MiB
'max_size' => 1024 * 1024 * 50,                   // 50 MiB
'allowed_extensions' => [ /* mime => ext map; '*' = any */ ],
'middleware' => ['web', 'auth'],
'route_prefix' => 'file-upload',
'soft_delete' => true,
'trash_ttl_days' => 30,
'public_url_resolver' => null,                    // fn(string $path, $disk): ?string
'full_access' => ['users' => [], 'roles' => []],  // who may manage any file
```

### S3 / CloudFront

Point `disk` at an `s3` filesystem. For signed/CDN URLs, set `public_url_resolver` to a closure
returning the URL for a path (the core never calls `$disk->url()` itself).

## Widget

Inject the widget service and render it in a Blade view:

```php
public function show(\Xakki\LaravelFileUploader\Services\FileWidget $widget)
{
    return view('page', ['uploader' => $widget->getWidget()]);
}
```

```blade
{!! $uploader !!}
```

It emits the mount point, the JS config (route URLs + CSRF token + flags) and the published UMD
widget from `public/vendor/file-uploader/file-uploader.umd.js`.

## HTTP API

Registered under `route_prefix` with the configured `middleware`:

| Method & path | Action |
|---|---|
| `POST {prefix}/chunks` | upload a chunk |
| `GET {prefix}/files` | list files |
| `DELETE {prefix}/files/{id}` | delete (soft by default) |
| `POST {prefix}/files/{id}/restore` | restore from trash |
| `DELETE {prefix}/trash/cleanup` | purge expired trash |

Responses use the shared Upload Protocol v1 envelope, identical to the Symfony binding and the demo.

## PHP service

```php
$widget = app(\Xakki\LaravelFileUploader\Services\FileWidget::class);
$files = $widget->list();
$widget->delete($id);
$widget->restore($id);
$widget->cleanupTrash();
```

`Services\FileUpload` (uploads) and `Services\FileWidget` (management + widget) are thin subclasses of
the core `FileUploader` / `FileManager`, wired to Laravel's disk, Auth, Date and logger.

## Console

```bash
php artisan file-uploader:cleanup          # purge expired trash
php artisan file-uploader:sync-metadata    # rebuild metadata from stored files
```

## i18n

Ships `en` and `ru`; publish and edit under `lang/vendor/file-uploader`.

## Test

```bash
composer install && composer phpunit
```
