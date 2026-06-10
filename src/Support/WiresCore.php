<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Psr\Log\NullLogger;
use Xakki\LaravelFileUploader\Auth\LaravelUserResolver;
use Xakki\LaravelFileUploader\Clock\LaravelClock;
use Xakki\LaravelFileUploader\Storage\LaravelStorage;

/**
 * Builds the core service from Laravel config: resolves the disk, wires the
 * Laravel-flavoured Storage/UserResolver/Logger/Clock seams into the core ctor.
 */
trait WiresCore
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        $config['disk'] = $config['disk'] ?? config('filesystems.default');

        parent::__construct(
            $config,
            new LaravelStorage(StorageFacade::disk($config['disk']), $config),
            new LaravelUserResolver,
            Log::getFacadeRoot() ?? new NullLogger,
            new LaravelClock,
        );
    }
}
