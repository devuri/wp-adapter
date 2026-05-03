<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Integration;

use AdapterKit\Core\Storage\WordPressTransientStorage;
use WP_UnitTestCase;

/**
 * @group integration
 */
final class WordPressTransientStorageTest extends WP_UnitTestCase
{
    private string $key = 'pp7core_test_transient';

    protected function setUp(): void
    {
        parent::setUp();
        delete_transient($this->key);
    }

    protected function tearDown(): void
    {
        delete_transient($this->key);
        parent::tearDown();
    }

    public function test_set_and_get_roundtrip(): void
    {
        $storage = new WordPressTransientStorage();
        $storage->set($this->key, 'cached_value', 3600);
        $this->assertSame('cached_value', $storage->get($this->key));
    }

    public function test_get_returns_false_when_transient_absent(): void
    {
        $storage = new WordPressTransientStorage();
        $this->assertFalse($storage->get($this->key));
    }

    public function test_delete_removes_transient(): void
    {
        $storage = new WordPressTransientStorage();
        $storage->set($this->key, 'value', 3600);
        $storage->delete($this->key);
        $this->assertFalse($storage->get($this->key));
    }
}
