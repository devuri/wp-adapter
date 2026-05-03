<?php

declare(strict_types=1);

namespace AdapterKit\Core\Time;

use AdapterKit\Core\Contracts\ClockInterface;

final class FrozenClock implements ClockInterface
{
    private int $ts;

    public function __construct(int $ts)
    {
        $this->ts = $ts;
    }

    public function now(): int
    {
        return $this->ts;
    }

    public function advance(int $seconds): void
    {
        $this->ts += $seconds;
    }
}
