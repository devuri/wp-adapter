<?php

declare(strict_types=1);

namespace AdapterKit\Core\Storage;

use AdapterKit\Core\Contracts\TransientStorageInterface;

final class WordPressTransientStorage implements TransientStorageInterface
{
    /**
     * @return mixed
     */
    public function get(string $key)
    {
        return get_transient($key);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, int $expiration): bool
    {
        return set_transient($key, $value, $expiration);
    }

    public function delete(string $key): bool
    {
        return delete_transient($key);
    }
}
