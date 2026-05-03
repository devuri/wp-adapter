<?php

/**
 * Composer usage example.
 * Install as a dev dependency: composer require --dev devuri/wp-adapter
 * Then copy into lib/ at build time: composer copy-core
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use AdapterKit\Core\Result;
use AdapterKit\Core\Support\KeyBuilder;
use AdapterKit\Core\Testing\InMemoryOptionStorage;
use AdapterKit\Core\Testing\RecordingLogger;

$keys    = new KeyBuilder('pp7_my_plugin');
$options = new InMemoryOptionStorage();
$logger  = new RecordingLogger();

$options->update($keys->option('settings'), ['enabled' => true]);
$logger->info('Settings updated');

$result = Result::success(['key' => $keys->option('settings')]);
var_dump($result->isSuccess()); // bool(true)
