<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\InMemoryTransientStorage;
use AdapterKit\Core\Time\FrozenClock;

final class InMemoryTransientStorageTest extends TestCase
{
    public function test_get_returns_false_when_key_does_not_exist(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $this->assertFalse($transients->get('missing'));
    }

    public function test_get_returns_stored_value_before_expiry(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $transients->set('key', 'value', 60);
        $this->assertSame('value', $transients->get('key'));
    }

    public function test_get_returns_false_after_expiry(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $transients->set('key', 'value', 60);
        $clock->advance(61);
        $this->assertFalse($transients->get('key'));
    }

    public function test_get_returns_value_exactly_at_expiry_boundary_is_expired(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $transients->set('key', 'value', 60);
        $clock->advance(60);
        $this->assertFalse($transients->get('key'));
    }

    public function test_set_returns_true(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $this->assertTrue($transients->set('key', 'value', 60));
    }

    public function test_delete_removes_entry(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $transients->set('key', 'value', 60);
        $transients->delete('key');
        $this->assertFalse($transients->get('key'));
    }

    public function test_delete_returns_true(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $this->assertTrue($transients->delete('key'));
    }

    public function test_expiration_zero_means_no_expiry(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $transients->set('key', 'persistent', 0);
        $clock->advance(999999);
        $this->assertSame('persistent', $transients->get('key'));
    }

    public function test_clock_and_storage_share_the_same_time_source(): void
    {
        $clock      = new FrozenClock(1700000000);
        $transients = new InMemoryTransientStorage($clock);
        $transients->set('key', 'value', 100);
        $clock->advance(50);
        $this->assertSame('value', $transients->get('key'));
        $clock->advance(51);
        $this->assertFalse($transients->get('key'));
    }
}
