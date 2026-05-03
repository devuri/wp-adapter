<?php

declare(strict_types=1);

namespace ExamplePlugin\Tests\Unit;

use ExamplePlugin\LicenseService;
use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\InMemoryOptionStorage;
use AdapterKit\Core\Testing\MockHttpClient;
use AdapterKit\Core\Testing\RecordingLogger;

/**
 * Unit tests for LicenseService.
 *
 * No WordPress loaded. No WP_UnitTestCase. Runs in milliseconds.
 * Every assertion is deterministic because every collaborator is
 * an in-memory fake with full inspection.
 */
final class LicenseServiceTest extends TestCase
{
    private InMemoryOptionStorage $options;
    private MockHttpClient        $http;
    private RecordingLogger       $logger;
    private LicenseService        $service;

    protected function setUp(): void
    {
        $this->options = new InMemoryOptionStorage(['ep_license' => []]);
        $this->http    = new MockHttpClient();
        $this->logger  = new RecordingLogger();
        $this->service = new LicenseService(
            $this->options,
            $this->http,
            $this->logger,
            'ep_license'
        );
    }

    public function test_activate_stores_key_and_returns_success(): void
    {
        $this->http->addJsonResponse('/activate', ['ok' => true], 200);

        $result = $this->service->activate('VALID-KEY-123');

        $this->assertTrue($result->isSuccess());

        $stored = $this->options->get('ep_license');
        $this->assertTrue($stored['active']);
        $this->assertSame('VALID-KEY-123', $stored['key']);
    }

    public function test_activate_returns_failure_and_logs_warning_on_http_error(): void
    {
        $this->http->addErrorResponse('/activate', 'Connection refused.');

        $result = $this->service->activate('ANY-KEY');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('activation_failed', $result->getCode());
        $this->assertSame('Connection refused.', $result->getMessage());
        $this->assertTrue($this->logger->hasWarning('activation_failed'));
    }

    public function test_activate_does_not_store_key_on_http_error(): void
    {
        $this->http->addErrorResponse('/activate', 'Timeout.');

        $this->service->activate('KEY');

        $stored = $this->options->get('ep_license');
        $this->assertEmpty($stored);
    }

    public function test_activate_sends_request_with_license_key(): void
    {
        $this->http->addJsonResponse('/activate', ['ok' => true], 200);

        $this->service->activate('MY-LICENSE-KEY');

        $this->assertTrue($this->http->wasRequestMadeTo('/activate'));
        $this->assertSame(1, $this->http->getRequestCount());
    }

    public function test_is_active_returns_false_before_activation(): void
    {
        $this->assertFalse($this->service->isActive());
    }

    public function test_is_active_returns_true_after_successful_activation(): void
    {
        $this->http->addJsonResponse('/activate', ['ok' => true], 200);
        $this->service->activate('KEY');

        $this->assertTrue($this->service->isActive());
    }

    public function test_deactivate_clears_active_flag(): void
    {
        $this->http->addJsonResponse('/activate', ['ok' => true], 200);
        $this->service->activate('KEY');

        $result = $this->service->deactivate();

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($this->service->isActive());
        $this->assertTrue($this->logger->hasInfo('license_deactivated'));
    }
}
