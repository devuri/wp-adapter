<?php

// Integration tests. Requires WP_TESTS_DIR to be set.
$testsDir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';

require_once $testsDir . '/includes/functions.php';

tests_add_filter(
    'muplugins_loaded',
    static function (): void {
        require dirname(__DIR__) . '/init.php';
    }
);

require $testsDir . '/includes/bootstrap.php';
