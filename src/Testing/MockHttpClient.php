<?php

declare(strict_types=1);

namespace AdapterKit\Core\Testing;

use AdapterKit\Core\Contracts\HttpClientInterface;

final class MockHttpClient implements HttpClientInterface
{
    /** @var array<string, array{is_error: bool, error_message: string|null, code: int, body: string}> */
    private array $responses = [];

    /** @var array<int, array{method: string, url: string, args: array}> */
    private array $requestHistory = [];

    /**
     * @param array<string, mixed> $body
     */
    public function addJsonResponse(string $urlFragment, array $body, int $code = 200): void
    {
        $json = json_encode($body);
        $this->responses[$urlFragment] = [
            'is_error'      => false,
            'error_message' => null,
            'code'          => $code,
            'body'          => false === $json ? '' : $json,
        ];
    }

    public function addErrorResponse(string $urlFragment, string $errorMessage): void
    {
        $this->responses[$urlFragment] = [
            'is_error'      => true,
            'error_message' => $errorMessage,
            'code'          => 0,
            'body'          => '',
        ];
    }

    /**
     * @param array{is_error: bool, error_message: string|null, code: int, body: string} $response
     */
    public function addRawResponse(string $urlFragment, array $response): void
    {
        $this->responses[$urlFragment] = $response;
    }

    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function get(string $url, array $args = []): array
    {
        return $this->dispatch('GET', $url, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function post(string $url, array $args = []): array
    {
        return $this->dispatch('POST', $url, $args);
    }

    public function wasRequestMadeTo(string $urlFragment): bool
    {
        foreach ($this->requestHistory as $request) {
            if (strpos($request['url'], $urlFragment) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array{method: string, url: string, args: array}|null
     */
    public function getLastRequest(): ?array
    {
        if (empty($this->requestHistory)) {
            return null;
        }
        return end($this->requestHistory);
    }

    public function getRequestCount(): int
    {
        return count($this->requestHistory);
    }

    /**
     * @return array<int, array{method: string, url: string, args: array}>
     */
    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    public function clear(): void
    {
        $this->requestHistory = [];
        $this->responses      = [];
    }

    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    private function dispatch(string $method, string $url, array $args): array
    {
        $this->requestHistory[] = ['method' => $method, 'url' => $url, 'args' => $args];

        foreach ($this->responses as $fragment => $response) {
            if (strpos($url, $fragment) !== false) {
                return $response;
            }
        }

        return [
            'is_error'      => true,
            'error_message' => 'No mock response registered for: ' . $url,
            'code'          => 0,
            'body'          => '',
        ];
    }
}
