<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Support\KeyBuilder;

final class KeyBuilderTest extends TestCase
{
    private KeyBuilder $keys;

    protected function setUp(): void
    {
        $this->keys = new KeyBuilder('pp7_clickable_links');
    }

    public function test_option_key_is_prefixed_with_underscore(): void
    {
        $this->assertSame('pp7_clickable_links_settings', $this->keys->option('settings'));
    }

    public function test_transient_key_is_prefixed_with_underscore(): void
    {
        $this->assertSame(
            'pp7_clickable_links_scan_result_123',
            $this->keys->transient('scan_result_123')
        );
    }

    public function test_hook_key_uses_forward_slash_separator(): void
    {
        $this->assertSame(
            'pp7_clickable_links/settings_saved',
            $this->keys->hook('settings_saved')
        );
    }

    public function test_cache_key_is_prefixed_with_underscore(): void
    {
        $this->assertSame('pp7_clickable_links_product_42', $this->keys->cache('product_42'));
    }

    public function test_different_prefixes_produce_different_keys(): void
    {
        $other = new KeyBuilder('pp7_other_plugin');
        $this->assertNotSame($this->keys->option('settings'), $other->option('settings'));
    }
}
