<?php

// Unit test bootstrap. WordPress is NOT loaded.
//
// This single line is enough: Composer's autoloader includes devuri/wp-adapter
// (and psr/log), so all contracts and testing adapters are available without
// WordPress, WP_TESTS_DIR, or any WordPress bootstrap file.
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
