<?php

/**
 * WP Adapter direct-load entry point.
 *
 * Plugins that bundle this package under lib/wp-adapter/ load it with:
 *   require_once __DIR__ . '/lib/wp-adapter/init.php';
 *
 * This registers two PSR-4 autoloaders:
 *   - AdapterKit\Core\ -> <this-dir>/src/
 *   - Psr\Log\       -> <this-dir>/psr-log/
 *
 * psr/log is pinned to ^1.1 (PHP 7.4-safe). The wp-adapter-copy binary
 * copies vendor/psr/log/Psr/Log/ into psr-log/ alongside src/ at build time.
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $base = __DIR__ . '/src/';
    $prefix = 'AdapterKit\\Core\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) === 0) {
        $relative = substr($class, $len);
        $file = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

spl_autoload_register(static function (string $class): void {
    $base = __DIR__ . '/psr-log/';
    $prefix = 'Psr\\Log\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) === 0) {
        $relative = substr($class, $len);
        $file = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
