<?php

declare(strict_types=1);

namespace AdapterKit\Core\Hooks;

use AdapterKit\Core\Contracts\HooksInterface;

final class WordPressHooks implements HooksInterface
{
    public function addAction(
        string $tag,
        callable $callback,
        int $priority = 10,
        int $args = 1
    ): void {
        add_action($tag, $callback, $priority, $args);
    }

    public function addFilter(
        string $tag,
        callable $callback,
        int $priority = 10,
        int $args = 1
    ): void {
        add_filter($tag, $callback, $priority, $args);
    }

    public function registerRestRoute(
        string $namespace,
        string $route,
        array $args
    ): bool {
        return (bool) register_rest_route($namespace, $route, $args);
    }
}
