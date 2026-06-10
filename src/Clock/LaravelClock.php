<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Clock;

use DateTimeImmutable;
use Illuminate\Support\Facades\Date;
use Psr\Clock\ClockInterface;

/**
 * PSR-20 clock backed by Laravel's Date facade so that Date::setTestNow() controls
 * time in tests and the core emits timestamps identical to the legacy behaviour.
 */
final class LaravelClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return Date::now()->toDateTimeImmutable();
    }
}
