<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\InMemoryOptionStorage;

final class InMemoryOptionStorageTest extends TestCase
{
    public function test_get_returns_false_when_key_does_not_exist(): void
    {
        $options = new InMemoryOptionStorage();
        $this->assertFalse($options->get('missing'));
    }

    public function test_get_returns_default_when_key_does_not_exist(): void
    {
        $options = new InMemoryOptionStorage();
        $this->assertSame('fallback', $options->get('missing', 'fallback'));
    }

    public function test_get_returns_stored_value_after_update(): void
    {
        $options = new InMemoryOptionStorage();
        $options->update('key', 'value');
        $this->assertSame('value', $options->get('key'));
    }

    public function test_update_returns_true(): void
    {
        $options = new InMemoryOptionStorage();
        $this->assertTrue($options->update('key', 'value'));
    }

    public function test_delete_removes_key(): void
    {
        $options = new InMemoryOptionStorage(['key' => 'value']);
        $options->delete('key');
        $this->assertFalse($options->get('key'));
    }

    public function test_delete_returns_true(): void
    {
        $options = new InMemoryOptionStorage(['key' => 'value']);
        $this->assertTrue($options->delete('key'));
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $options = new InMemoryOptionStorage(['key' => 'value']);
        $this->assertTrue($options->has('key'));
    }

    public function test_has_returns_false_when_key_does_not_exist(): void
    {
        $options = new InMemoryOptionStorage();
        $this->assertFalse($options->has('missing'));
    }

    public function test_all_returns_full_store_contents(): void
    {
        $initial = ['a' => 1, 'b' => 2];
        $options = new InMemoryOptionStorage($initial);
        $this->assertSame($initial, $options->all());
    }

    public function test_clear_empties_the_store(): void
    {
        $options = new InMemoryOptionStorage(['a' => 1]);
        $options->clear();
        $this->assertSame([], $options->all());
    }

    public function test_initial_values_are_accessible_via_get(): void
    {
        $options = new InMemoryOptionStorage(['pp7_settings' => ['enabled' => true]]);
        $this->assertSame(['enabled' => true], $options->get('pp7_settings'));
    }
}
