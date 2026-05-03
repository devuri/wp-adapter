<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\RecordingHooks;

final class RecordingHooksTest extends TestCase
{
    public function test_has_action_returns_true_after_registration(): void
    {
        $hooks = new RecordingHooks();
        $hooks->addAction('admin_menu', static function (): void {});
        $this->assertTrue($hooks->hasAction('admin_menu'));
    }

    public function test_has_action_returns_false_when_not_registered(): void
    {
        $hooks = new RecordingHooks();
        $this->assertFalse($hooks->hasAction('admin_menu'));
    }

    public function test_has_filter_returns_true_after_registration(): void
    {
        $hooks = new RecordingHooks();
        $hooks->addFilter('the_content', static function (string $content): string {
            return $content;
        });
        $this->assertTrue($hooks->hasFilter('the_content'));
    }

    public function test_has_filter_returns_false_when_not_registered(): void
    {
        $hooks = new RecordingHooks();
        $this->assertFalse($hooks->hasFilter('the_content'));
    }

    public function test_has_rest_route_returns_true_after_registration(): void
    {
        $hooks = new RecordingHooks();
        $hooks->registerRestRoute('my-plugin/v1', '/settings', []);
        $this->assertTrue($hooks->hasRestRoute('/settings'));
    }

    public function test_has_rest_route_returns_false_when_not_registered(): void
    {
        $hooks = new RecordingHooks();
        $this->assertFalse($hooks->hasRestRoute('/settings'));
    }

    public function test_get_actions_returns_all_registered_actions(): void
    {
        $hooks = new RecordingHooks();
        $hooks->addAction('init', static function (): void {});
        $hooks->addAction('admin_menu', static function (): void {});
        $this->assertCount(2, $hooks->getActions());
    }

    public function test_clear_removes_all_recorded_hooks(): void
    {
        $hooks = new RecordingHooks();
        $hooks->addAction('init', static function (): void {});
        $hooks->addFilter('the_content', static function (string $c): string {
            return $c;
        });
        $hooks->clear();
        $this->assertFalse($hooks->hasAction('init'));
        $this->assertFalse($hooks->hasFilter('the_content'));
    }
}
