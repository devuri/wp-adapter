<?php

declare(strict_types=1);

namespace AdapterKit\Core\Support;

final class KeyBuilder
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function option(string $name): string
    {
        return $this->prefix . '_' . $name;
    }

    public function transient(string $name): string
    {
        return $this->prefix . '_' . $name;
    }

    public function hook(string $name): string
    {
        return $this->prefix . '/' . $name;
    }

    public function cache(string $name): string
    {
        return $this->prefix . '_' . $name;
    }
}
