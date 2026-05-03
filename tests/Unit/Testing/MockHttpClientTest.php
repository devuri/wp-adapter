<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\MockHttpClient;

final class MockHttpClientTest extends TestCase
{
    public function test_post_returns_registered_json_response(): void
    {
        $http = new MockHttpClient();
        $http->addJsonResponse('/activate', ['ok' => true], 200);
        $response = $http->post('https://api.example.com/activate', []);
        $this->assertFalse($response['is_error']);
        $this->assertSame(200, $response['code']);
        $this->assertSame('{"ok":true}', $response['body']);
    }

    public function test_post_returns_error_response_when_registered(): void
    {
        $http = new MockHttpClient();
        $http->addErrorResponse('/timeout', 'Request timed out.');
        $response = $http->post('https://api.example.com/timeout', []);
        $this->assertTrue($response['is_error']);
        $this->assertSame('Request timed out.', $response['error_message']);
    }

    public function test_get_returns_registered_response(): void
    {
        $http = new MockHttpClient();
        $http->addJsonResponse('/status', ['active' => true], 200);
        $response = $http->get('https://api.example.com/status');
        $this->assertFalse($response['is_error']);
    }

    public function test_returns_error_when_no_response_registered(): void
    {
        $http     = new MockHttpClient();
        $response = $http->post('https://api.example.com/unknown', []);
        $this->assertTrue($response['is_error']);
    }

    public function test_was_request_made_to_returns_true_for_matching_url(): void
    {
        $http = new MockHttpClient();
        $http->addJsonResponse('/activate', ['ok' => true], 200);
        $http->post('https://api.example.com/activate', []);
        $this->assertTrue($http->wasRequestMadeTo('/activate'));
    }

    public function test_was_request_made_to_returns_false_when_no_requests(): void
    {
        $http = new MockHttpClient();
        $this->assertFalse($http->wasRequestMadeTo('/activate'));
    }

    public function test_get_last_request_returns_null_when_no_requests_made(): void
    {
        $http = new MockHttpClient();
        $this->assertNull($http->getLastRequest());
    }

    public function test_get_last_request_returns_most_recent_request(): void
    {
        $http = new MockHttpClient();
        $http->addJsonResponse('/a', [], 200);
        $http->addJsonResponse('/b', [], 200);
        $http->post('https://api.example.com/a', []);
        $http->post('https://api.example.com/b', ['body' => 'test']);
        $last = $http->getLastRequest();
        $this->assertNotNull($last);
        $this->assertStringContainsString('/b', $last['url']);
    }

    public function test_get_request_count_reflects_number_of_requests_made(): void
    {
        $http = new MockHttpClient();
        $http->addJsonResponse('/endpoint', [], 200);
        $http->post('https://api.example.com/endpoint', []);
        $http->post('https://api.example.com/endpoint', []);
        $this->assertSame(2, $http->getRequestCount());
    }
}
