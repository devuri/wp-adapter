<?php

/**
 * Plugin Name: Example Plugin
 * Version:     0.1.0
 * Text Domain: example-plugin
 *
 * Demonstrates how a AdapterKit enabled plugin wires WP Adapter adapters at the edge.
 * The vendor/ directory is stripped before distribution; lib/ is populated
 * by running: vendor/bin/wp-adapter-copy
 */

declare(strict_types=1);

use AdapterKit\Core\PluginContext;
use AdapterKit\Core\Hooks\WordPressHooks;
use AdapterKit\Core\Storage\WordPressOptionStorage;
use AdapterKit\Core\Storage\WordPressTransientStorage;
use AdapterKit\Core\Http\WordPressHttpClient;
use AdapterKit\Core\Logging\NullLogger;

require_once __DIR__ . '/lib/wp-adapter/init.php';

$context = PluginContext::fromPluginFile(
    __FILE__,
    'example-plugin',
    '0.1.0',
    'example-plugin',
    'pp7_example'
);

$plugin = new ExamplePlugin\Plugin(
    $context,
    new WordPressHooks(),
    new WordPressOptionStorage(),
    new WordPressTransientStorage(),
    new WordPressHttpClient(),
    new NullLogger()
);

$plugin->register();
