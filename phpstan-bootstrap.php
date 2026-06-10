<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Facade;

// Minimal Laravel container so larastan can resolve the Storage/config facades the
// package uses, without booting Testbench. Scratch disk paths live in the temp dir.

$container = new Container;

Container::setInstance($container);
Facade::setFacadeApplication($container);

$storagePath = sys_get_temp_dir().'/lfu-laravel-phpstan';
$defaultDiskPath = $storagePath.'/app';
$filesDiskPath = $storagePath.'/files';

foreach ([$defaultDiskPath, $filesDiskPath] as $path) {
    if (! is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

if (! method_exists($container, 'configPath')) {
    $container = new class extends Container
    {
        protected function joinPath(string $root, string $path = ''): string
        {
            if ($path === '') {
                return $root;
            }

            return $root.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        }

        public function basePath(string $path = ''): string
        {
            return $this->joinPath(__DIR__, $path);
        }

        public function configPath(string $path = ''): string
        {
            return $this->joinPath($this->basePath('config'), $path);
        }

        public function databasePath(string $path = ''): string
        {
            return $this->joinPath($this->basePath('database'), $path);
        }
    };

    Container::setInstance($container);
}

$container->instance('config', new Repository([
    'filesystems' => [
        'default' => 'local',
        'cloud' => 'local',
        'disks' => [
            'public' => [
                'driver' => 'local',
                'root' => $defaultDiskPath,
                'throw' => false,
            ],
        ],
        'links' => [],
    ],
]));

$container->singleton('files', function () {
    return new Filesystem;
});

$container->singleton('filesystem', function ($app) {
    return new FilesystemManager($app);
});

$container->bind('filesystem.disk', function ($app) {
    return $app['filesystem']->disk($app['config']->get('filesystems.default'));
});

$container->bind('filesystem.cloud', function ($app) {
    return $app['filesystem']->disk($app['config']->get('filesystems.cloud'));
});
