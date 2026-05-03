<?php

declare(strict_types=1);

namespace AdapterKit\Core\Contracts;

interface TransientStorageInterface
{
    /**
     * @return mixed
     */
    public function get(string $key);

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, int $expiration): bool;

    public function delete(string $key): bool;
}
