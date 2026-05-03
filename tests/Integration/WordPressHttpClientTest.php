<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Integration;

use AdapterKit\Core\Http\WordPressHttpClient;
use WP_UnitTestCase;

/**
 * @group integration
 */
final class WordPressHttpClientTest extends WP_UnitTestCase
{
    public function test_get_returns_structured_response_array(): void
    {
        $client   = new WordPressHttpClient();
        $response = $client->get('https://httpbin.org/get');

        $this->assertArrayHasKey('is_error', $response);
        $this->assertArrayHasKey('error_message', $response);
        $this->assertArrayHasKey('code', $response);
        $this->assertArrayHasKey('body', $response);
    }

    public function test_get_returns_error_response_for_invalid_url(): void
    {
        $client   = new WordPressHttpClient();
        $response = $client->get('http://localhost:0/unreachable');

        $this->assertIsBool($response['is_error']);
    }
}
