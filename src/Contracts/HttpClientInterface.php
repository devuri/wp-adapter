<?php

declare(strict_types=1);

namespace AdapterKit\Core\Contracts;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function get(string $url, array $args = []): array;

    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function post(string $url, array $args = []): array;
}
