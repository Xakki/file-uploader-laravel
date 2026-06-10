<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Services;

use Xakki\FileUploader\FileManager;
use Xakki\LaravelFileUploader\Support\WiresCore;

/**
 * Laravel-wired manager + widget. Inherits list/delete/restore/cleanup from the core
 * FileManager and adds the server-rendered widget bootstrap (routes + CSRF token).
 */
class FileWidget extends FileManager
{
    use WiresCore;

    /**
     * @param  array<string, mixed>  $config
     */
    public function getWidget(array $config = []): string
    {
        $config['endpointBase'] = '/'.$this->config['route_prefix'];
        $config['chunkSize'] = $this->config['chunk_size'];
        $config['allowList'] = (bool) $this->config['allow_list'];
        $config['allowDelete'] = (bool) $this->config['allow_delete'];
        $config['allowDeleteAllFiles'] = (bool) $this->config['allow_delete_all_files'];
        $config['allowCleanup'] = (bool) $this->config['allow_cleanup'];
        $config['locale'] = $this->config['locale'];
        $config['token'] = csrf_token();
        $config['routePlaceholder'] = self::ROUTE_PARAM_PLACEHOLDER;
        $config['routes'] = [
            'upload' => route($this->config['route_name'].'chunks.store'),
            'list' => route($this->config['route_name'].'files.index'),
            'delete' => route($this->config['route_name'].'files.destroy', ['id' => self::ROUTE_PARAM_PLACEHOLDER]),
            'restore' => route($this->config['route_name'].'files.restore', ['id' => self::ROUTE_PARAM_PLACEHOLDER]),
            'cleanup' => route($this->config['route_name'].'trash.cleanup'),
        ];

        return '
            <div id="file-upload-widget"></div>
            <script>
              window.FileUploadConfig = '.json_encode($config).';
            </script>
            <script src="/vendor/file-uploader/file-uploader.umd.js" defer></script>
            ';
    }
}
