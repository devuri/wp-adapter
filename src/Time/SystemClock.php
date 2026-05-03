<?php

declare(strict_types=1);

namespace AdapterKit\Core\Time;

use AdapterKit\Core\Contracts\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): int
    {
        return time();
    }
}
