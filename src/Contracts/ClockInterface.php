<?php

declare(strict_types=1);

namespace AdapterKit\Core\Contracts;

interface ClockInterface
{
    public function now(): int;
}
