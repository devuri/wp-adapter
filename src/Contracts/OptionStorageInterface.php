<?php

declare(strict_types=1);

namespace AdapterKit\Core\Contracts;

interface OptionStorageInterface
{
    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = false);

    /**
     * @param mixed $value
     */
    public function update(string $key, $value, ?bool $autoload = null): bool;

    public function delete(string $key): bool;
}
