# Migration — Laravel package 0.2 → 1.0

`xakki/laravel-file-uploader` 1.0 became a **thin binding over the new framework-agnostic core**
([`xakki/file-uploader`](https://github.com/Xakki/file-uploader)). The upload logic moved out of the package into the core, but the
**public Laravel API is preserved** — for most apps the upgrade is `composer update` + republish
assets.

## TL;DR

```bash
composer require xakki/laravel-file-uploader:^1.0
php artisan vendor:publish --tag=file-uploader-assets --force   # widget asset changed
php artisan view:clear
```

`xakki/file-uploader` (the core) is pulled in automatically as a dependency.

## What did **not** change

- **Config** — `config/file-uploader.php` and all its keys are unchanged.
- **Routes & HTTP API** — same prefix, middleware, endpoints and response envelope.
- **Widget usage** — `FileWidget::getWidget()` rendered in Blade, same as before.
- **Service FQCNs** — `Xakki\LaravelFileUploader\Services\FileUpload` and `…\Services\FileWidget`
  still exist at the same names (now thin subclasses of the core). Their public methods are kept.
- **Console** — `file-uploader:cleanup`, `file-uploader:sync-metadata`.
- **Requirements** — PHP `^8.3`, Laravel `10 | 11 | 12` (unchanged).

## What changed

### Widget asset (action required)

The front-end was rewritten from two hand-written vanilla-JS files into a single pre-built **UMD
bundle** (built from [`@xakki/file-uploader`](https://github.com/Xakki/file-uploader/tree/main/js)). The published asset is now:

```
public/vendor/file-uploader/file-uploader.umd.js
```

`FileWidget::getWidget()` emits **one** `<script src=".../file-uploader.umd.js" defer>` instead of the
old pair. Re-publish with `--force` (this also runs automatically on `package:discover`). If you
referenced the old `file-upload.js` / `file-widget.js` files directly, switch to the UMD bundle (or
use the npm package `@xakki/file-uploader/widget`).

### Internal classes moved to the core

Only relevant if you imported or extended package internals:

| 0.2 (Laravel package) | 1.0 (core) | Note |
|---|---|---|
| `Xakki\LaravelFileUploader\DTO\FileMetadata` | `Xakki\FileUploader\Dto\FileMetadata` | old FQCN kept as a `class_alias` — `instanceof` / type-hints keep working |
| upload/manager logic in `Services\FileUpload` / `FileWidget` | `Xakki\FileUploader\FileUploader` / `FileManager` | Laravel services now subclass these; public surface preserved |

The `class_alias` (in `src/aliases.php`) means existing code referencing the old
`DTO\FileMetadata` name continues to work. New code should use `Xakki\FileUploader\Dto\FileMetadata`.

If you **deep-integrated** by importing other internal classes directly (DTOs, exceptions, the
storage/auth glue), update the namespace from `Xakki\LaravelFileUploader\…` to `Xakki\FileUploader\…`
where the class now lives in the core. The Laravel-specific glue (controllers, FormRequest, service
provider, the `Services\*` classes) stays under `Xakki\LaravelFileUploader\`.

## Behaviour

The internals were refactored, not rewritten — the existing Testbench test suite stays green against
the extracted core, so upload/list/delete/restore/trash behaviour is unchanged.

## New in 1.0

- A reusable, framework-agnostic **core** you can mount in Symfony or any PHP app.
- An npm package `@xakki/file-uploader` (headless client + widget) for SPAs.
- A formal **[Upload Protocol v1](https://github.com/Xakki/file-uploader/blob/main/protocol/SPEC.md)** with shared conformance fixtures.
