<?php

declare(strict_types=1);

namespace AdapterKit\Core\Storage;

use AdapterKit\Core\Contracts\OptionStorageInterface;

class WordPressOptionStorage implements OptionStorageInterface
{
    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = false)
    {
        return get_option($key, $default);
    }

    /**
     * @param mixed $value
     */
    public function update(string $key, $value, ?bool $autoload = null): bool
    {
        return update_option($key, $value, $autoload);
    }

    public function delete(string $key): bool
    {
        return delete_option($key);
    }
}
