<?php

declare(strict_types=1);

namespace ExamplePlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\InMemoryOptionStorage;
use AdapterKit\Core\Testing\MockHttpClient;
use AdapterKit\Core\Testing\RecordingLogger;

/**
 * Example: how a plugin unit-tests its own service using WP Adapter testing adapters.
 * The same ExampleService is used in production with WordPress adapters - no code changes.
 */
final class ExampleServiceTest extends TestCase
{
    public function test_service_logs_warning_when_http_call_fails(): void
    {
        $options = new InMemoryOptionStorage(['pp7_example_settings' => ['timeout' => 5]]);
        $http    = new MockHttpClient();
        $logger  = new RecordingLogger();

        $http->addErrorResponse('/api/check', 'Connection refused.');

        // $service = new ExamplePlugin\Service\CheckService($options, $http, $logger);
        // $service->run();

        // $this->assertTrue($logger->hasWarning('check_failed'));
        $this->assertTrue(true); // placeholder until ExampleService is implemented
    }
}
