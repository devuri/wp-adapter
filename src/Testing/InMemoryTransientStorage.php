<?php

declare(strict_types=1);

namespace AdapterKit\Core\Testing;

use AdapterKit\Core\Contracts\ClockInterface;
use AdapterKit\Core\Contracts\TransientStorageInterface;

final class InMemoryTransientStorage implements TransientStorageInterface
{
    private ClockInterface $clock;

    /** @var array<string, array{value: mixed, expires_at: int}> */
    private array $store = [];

    public function __construct(ClockInterface $clock)
    {
        $this->clock = $clock;
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        if (!array_key_exists($key, $this->store)) {
            return false;
        }

        $entry = $this->store[$key];

        if ($entry['expires_at'] > 0 && $this->clock->now() >= $entry['expires_at']) {
            unset($this->store[$key]);
            return false;
        }

        return $entry['value'];
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, int $expiration): bool
    {
        $this->store[$key] = [
            'value'      => $value,
            'expires_at' => $expiration > 0 ? $this->clock->now() + $expiration : 0,
        ];
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);
        return true;
    }
}
