<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AdapterKit\Core\PluginContext;

final class PluginContextTest extends TestCase
{
    private PluginContext $context;

    protected function setUp(): void
    {
        $this->context = PluginContext::fromValues(
            'my-plugin',
            '1.0.0',
            '/var/www/wp-content/plugins/my-plugin/my-plugin.php',
            'my-plugin/my-plugin.php',
            '/var/www/wp-content/plugins/my-plugin/',
            'https://example.com/wp-content/plugins/my-plugin/',
            'my-plugin',
            'pp7_my_plugin'
        );
    }

    public function test_get_slug_returns_correct_value(): void
    {
        $this->assertSame('my-plugin', $this->context->getSlug());
    }

    public function test_get_version_returns_correct_value(): void
    {
        $this->assertSame('1.0.0', $this->context->getVersion());
    }

    public function test_get_file_returns_correct_value(): void
    {
        $this->assertSame(
            '/var/www/wp-content/plugins/my-plugin/my-plugin.php',
            $this->context->getFile()
        );
    }

    public function test_get_basename_returns_correct_value(): void
    {
        $this->assertSame('my-plugin/my-plugin.php', $this->context->getBasename());
    }

    public function test_get_dir_path_returns_correct_value(): void
    {
        $this->assertSame(
            '/var/www/wp-content/plugins/my-plugin/',
            $this->context->getDirPath()
        );
    }

    public function test_get_dir_url_returns_correct_value(): void
    {
        $this->assertSame(
            'https://example.com/wp-content/plugins/my-plugin/',
            $this->context->getDirUrl()
        );
    }

    public function test_get_text_domain_returns_correct_value(): void
    {
        $this->assertSame('my-plugin', $this->context->getTextDomain());
    }

    public function test_get_option_prefix_returns_correct_value(): void
    {
        $this->assertSame('pp7_my_plugin', $this->context->getOptionPrefix());
    }
}
