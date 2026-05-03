<?php

declare(strict_types=1);

namespace AdapterKit\Core\Contracts;

interface HooksInterface
{
    public function addAction(
        string $tag,
        callable $callback,
        int $priority = 10,
        int $args = 1
    ): void;

    public function addFilter(
        string $tag,
        callable $callback,
        int $priority = 10,
        int $args = 1
    ): void;

    public function registerRestRoute(
        string $namespace,
        string $route,
        array $args
    ): bool;
}
