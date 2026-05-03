<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Integration;

use AdapterKit\Core\Storage\WordPressOptionStorage;
use WP_UnitTestCase;

/**
 * @group integration
 */
final class WordPressOptionStorageTest extends WP_UnitTestCase
{
    private string $key = 'pp7core_test_option';

    protected function setUp(): void
    {
        parent::setUp();
        delete_option($this->key);
    }

    protected function tearDown(): void
    {
        delete_option($this->key);
        parent::tearDown();
    }

    public function test_update_and_get_roundtrip(): void
    {
        $storage = new WordPressOptionStorage();
        $storage->update($this->key, ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $storage->get($this->key));
    }

    public function test_get_returns_default_when_option_absent(): void
    {
        $storage = new WordPressOptionStorage();
        $this->assertFalse($storage->get($this->key));
    }

    public function test_delete_removes_option(): void
    {
        $storage = new WordPressOptionStorage();
        $storage->update($this->key, 'value');
        $storage->delete($this->key);
        $this->assertFalse($storage->get($this->key));
    }
}
