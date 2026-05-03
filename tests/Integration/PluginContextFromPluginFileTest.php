<?php

declare(strict_types=1);

namespace AdapterKit\Core\Tests\Integration;

use AdapterKit\Core\PluginContext;
use WP_UnitTestCase;

/**
 * @group integration
 */
final class PluginContextFromPluginFileTest extends WP_UnitTestCase
{
    public function test_from_plugin_file_populates_basename_via_wordpress(): void
    {
        // Use a known plugin file that WordPress will resolve correctly.
        $file    = WP_PLUGIN_DIR . '/hello.php';
        $context = PluginContext::fromPluginFile($file, 'hello', '1.0.0', 'hello', 'hello');

        $this->assertSame('hello.php', $context->getBasename());
    }

    public function test_from_plugin_file_populates_dir_path_via_wordpress(): void
    {
        $file    = WP_PLUGIN_DIR . '/hello.php';
        $context = PluginContext::fromPluginFile($file, 'hello', '1.0.0', 'hello', 'hello');

        $this->assertStringEndsWith('/', $context->getDirPath());
        $this->assertDirectoryExists($context->getDirPath());
    }

    public function test_from_plugin_file_dir_url_ends_with_slash(): void
    {
        $file    = WP_PLUGIN_DIR . '/hello.php';
        $context = PluginContext::fromPluginFile($file, 'hello', '1.0.0', 'hello', 'hello');

        $this->assertStringEndsWith('/', $context->getDirUrl());
    }
}
