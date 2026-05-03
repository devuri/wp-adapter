<?php

/**
 * Direct-load example: no Composer at runtime.
 * Plugins bundle WP Adapter under lib/wp-adapter/ via wp-adapter-copy.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/wp-adapter/init.php';

// All AdapterKit\Core classes are now available, including Testing\.
use AdapterKit\Core\PluginContext;
use AdapterKit\Core\Result;
use AdapterKit\Core\Support\KeyBuilder;
use AdapterKit\Core\Time\FrozenClock;
use AdapterKit\Core\Testing\InMemoryOptionStorage;

$keys   = new KeyBuilder('pp7_example');
$clock  = new FrozenClock(time());
$result = Result::success(['loaded' => true]);
