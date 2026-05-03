<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit\Time;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Time\FrozenClock;

final class FrozenClockTest extends TestCase
{
    public function test_now_returns_frozen_timestamp(): void
    {
        $clock = new FrozenClock(1700000000);
        $this->assertSame(1700000000, $clock->now());
    }

    public function test_now_does_not_change_without_advance(): void
    {
        $clock = new FrozenClock(1700000000);
        $first  = $clock->now();
        $second = $clock->now();
        $this->assertSame($first, $second);
    }

    public function test_advance_increases_timestamp_by_given_seconds(): void
    {
        $clock = new FrozenClock(1700000000);
        $clock->advance(60);
        $this->assertSame(1700000060, $clock->now());
    }

    public function test_advance_is_cumulative(): void
    {
        $clock = new FrozenClock(1700000000);
        $clock->advance(30);
        $clock->advance(30);
        $this->assertSame(1700000060, $clock->now());
    }
}
