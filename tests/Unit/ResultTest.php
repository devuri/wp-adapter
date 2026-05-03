<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Result;

final class ResultTest extends TestCase
{
    public function test_success_is_success(): void
    {
        $result = Result::success();
        $this->assertTrue($result->isSuccess());
    }

    public function test_success_code_is_success_string(): void
    {
        $result = Result::success();
        $this->assertSame('success', $result->getCode());
    }

    public function test_success_message_is_empty(): void
    {
        $result = Result::success();
        $this->assertSame('', $result->getMessage());
    }

    public function test_success_returns_provided_data(): void
    {
        $result = Result::success(['saved' => true]);
        $this->assertSame(['saved' => true], $result->getData());
    }

    public function test_failure_is_not_success(): void
    {
        $result = Result::failure('invalid_input', 'Bad input.');
        $this->assertFalse($result->isSuccess());
    }

    public function test_failure_returns_correct_code(): void
    {
        $result = Result::failure('invalid_input', 'Bad input.');
        $this->assertSame('invalid_input', $result->getCode());
    }

    public function test_failure_returns_correct_message(): void
    {
        $result = Result::failure('invalid_input', 'Bad input.');
        $this->assertSame('Bad input.', $result->getMessage());
    }

    public function test_failure_returns_provided_data(): void
    {
        $result = Result::failure('err', 'msg', ['field' => 'email']);
        $this->assertSame(['field' => 'email'], $result->getData());
    }

    public function test_failure_data_defaults_to_empty_array(): void
    {
        $result = Result::failure('err', 'msg');
        $this->assertSame([], $result->getData());
    }
}
