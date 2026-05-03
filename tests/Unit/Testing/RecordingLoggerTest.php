<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\RecordingLogger;

final class RecordingLoggerTest extends TestCase
{
    public function test_has_warning_returns_true_after_warning_logged(): void
    {
        $logger = new RecordingLogger();
        $logger->warning('rate_limit_exceeded');
        $this->assertTrue($logger->hasWarning('rate_limit_exceeded'));
    }

    public function test_has_warning_returns_false_when_no_warnings_logged(): void
    {
        $logger = new RecordingLogger();
        $this->assertFalse($logger->hasWarning('rate_limit_exceeded'));
    }

    public function test_has_error_returns_true_after_error_logged(): void
    {
        $logger = new RecordingLogger();
        $logger->error('connection_failed');
        $this->assertTrue($logger->hasError('connection_failed'));
    }

    public function test_has_info_returns_true_after_info_logged(): void
    {
        $logger = new RecordingLogger();
        $logger->info('process_started');
        $this->assertTrue($logger->hasInfo('process_started'));
    }

    public function test_has_debug_returns_true_after_debug_logged(): void
    {
        $logger = new RecordingLogger();
        $logger->debug('query_executed');
        $this->assertTrue($logger->hasDebug('query_executed'));
    }

    public function test_get_errors_returns_only_error_entries(): void
    {
        $logger = new RecordingLogger();
        $logger->info('info message');
        $logger->error('error message');
        $errors = $logger->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('error', $errors[0]['level']);
    }

    public function test_count_returns_correct_count_for_level(): void
    {
        $logger = new RecordingLogger();
        $logger->warning('first');
        $logger->warning('second');
        $logger->error('an error');
        $this->assertSame(2, $logger->count('warning'));
        $this->assertSame(1, $logger->count('error'));
    }

    public function test_clear_removes_all_entries(): void
    {
        $logger = new RecordingLogger();
        $logger->error('something');
        $logger->clear();
        $this->assertSame([], $logger->all());
    }

    public function test_all_returns_all_entries_in_order(): void
    {
        $logger = new RecordingLogger();
        $logger->info('first');
        $logger->warning('second');
        $entries = $logger->all();
        $this->assertCount(2, $entries);
        $this->assertSame('info', $entries[0]['level']);
        $this->assertSame('warning', $entries[1]['level']);
    }
}
