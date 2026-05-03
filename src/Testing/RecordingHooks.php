<?php

declare(strict_types=1);

namespace AdapterKit\Core\Testing;

use AdapterKit\Core\Contracts\HooksInterface;

final class RecordingHooks implements HooksInterface
{
    /** @var array<int, array{tag: string, callback: callable, priority: int, args: int}> */
    private array $actions = [];

    /** @var array<int, array{tag: string, callback: callable, priority: int, args: int}> */
    private array $filters = [];

    /** @var array<int, array{namespace: string, route: string, args: array}> */
    private array $restRoutes = [];

    public function addAction(
        string $tag,
        callable $callback,
        int $priority = 10,
        int $args = 1
    ): void {
        $this->actions[] = [
            'tag'      => $tag,
            'callback' => $callback,
            'priority' => $priority,
            'args'     => $args,
        ];
    }

    public function addFilter(
        string $tag,
        callable $callback,
        int $priority = 10,
        int $args = 1
    ): void {
        $this->filters[] = [
            'tag'      => $tag,
            'callback' => $callback,
            'priority' => $priority,
            'args'     => $args,
        ];
    }

    public function registerRestRoute(
        string $namespace,
        string $route,
        array $args
    ): bool {
        $this->restRoutes[] = [
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
        ];
        return true;
    }

    public function hasAction(string $tag): bool
    {
        foreach ($this->actions as $action) {
            if ($action['tag'] === $tag) {
                return true;
            }
        }
        return false;
    }

    public function hasFilter(string $tag): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter['tag'] === $tag) {
                return true;
            }
        }
        return false;
    }

    public function hasRestRoute(string $route): bool
    {
        foreach ($this->restRoutes as $registered) {
            if ($registered['route'] === $route) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<int, array{tag: string, callback: callable, priority: int, args: int}>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return array<int, array{tag: string, callback: callable, priority: int, args: int}>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array<int, array{namespace: string, route: string, args: array}>
     */
    public function getRestRoutes(): array
    {
        return $this->restRoutes;
    }

    public function clear(): void
    {
        $this->actions    = [];
        $this->filters    = [];
        $this->restRoutes = [];
    }
}
