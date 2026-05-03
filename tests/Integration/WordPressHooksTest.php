<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Integration;

use AdapterKit\Core\Hooks\WordPressHooks;
use WP_UnitTestCase;

/**
 * @group integration
 */
final class WordPressHooksTest extends WP_UnitTestCase
{
    private string $actionTag = 'pp7core_test_action';
    private string $filterTag = 'pp7core_test_filter';

    protected function tearDown(): void
    {
        remove_all_actions($this->actionTag);
        remove_all_filters($this->filterTag);
        parent::tearDown();
    }

    public function test_add_action_registers_with_wordpress(): void
    {
        $hooks = new WordPressHooks();
        $hooks->addAction($this->actionTag, static function (): void {});
        $this->assertNotFalse(has_action($this->actionTag));
    }

    public function test_add_filter_registers_with_wordpress(): void
    {
        $hooks = new WordPressHooks();
        $hooks->addFilter($this->filterTag, static function (string $v): string {
            return $v;
        });
        $this->assertNotFalse(has_filter($this->filterTag));
    }
}
