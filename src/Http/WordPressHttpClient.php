<?php

declare(strict_types=1);

namespace AdapterKit\Core\Http;

use AdapterKit\Core\Contracts\HttpClientInterface;

final class WordPressHttpClient implements HttpClientInterface
{
    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function get(string $url, array $args = []): array
    {
        $response = wp_remote_get($url, $args);
        return $this->parseResponse($response);
    }

    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function post(string $url, array $args = []): array
    {
        $response = wp_remote_post($url, $args);
        return $this->parseResponse($response);
    }

    /**
     * @param mixed $response
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    private function parseResponse($response): array
    {
        if (is_wp_error($response)) {
            return [
                'is_error'      => true,
                'error_message' => $response->get_error_message(),
                'code'          => 0,
                'body'          => '',
            ];
        }

        return [
            'is_error'      => false,
            'error_message' => null,
            'code'          => (int) wp_remote_retrieve_response_code($response),
            'body'          => (string) wp_remote_retrieve_body($response),
        ];
    }
}
