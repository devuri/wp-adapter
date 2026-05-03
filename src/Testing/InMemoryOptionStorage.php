<?php

declare(strict_types=1);

namespace AdapterKit\Core\Testing;

use AdapterKit\Core\Contracts\OptionStorageInterface;

final class InMemoryOptionStorage implements OptionStorageInterface
{
    private array $store;

    public function __construct(array $initial = [])
    {
        $this->store = $initial;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = false)
    {
        return array_key_exists($key, $this->store) ? $this->store[$key] : $default;
    }

    /**
     * @param mixed $value
     */
    public function update(string $key, $value, ?bool $autoload = null): bool
    {
        $this->store[$key] = $value;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function all(): array
    {
        return $this->store;
    }

    public function clear(): void
    {
        $this->store = [];
    }
}
