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

$buildIdFile = __DIR__ . '/.build-id';
$buildId = is_file($buildIdFile) ? trim((string) file_get_contents($buildIdFile)) : '';
$registryKey = '__devuri_wp_adapter_build';

if ($buildId === '') {
    throw new RuntimeException('WP Adapter build ID is missing or unreadable: ' . $buildIdFile);
}

if (isset($GLOBALS[$registryKey])) {
    $loaded = $GLOBALS[$registryKey];

    if ($loaded['id'] !== $buildId) {
        throw new RuntimeException(sprintf(
            'Conflicting WP Adapter builds detected: %s at %s; %s at %s.',
            $loaded['id'],
            $loaded['path'],
            $buildId,
            __DIR__
        ));
    }

    return;
}

$GLOBALS[$registryKey] = [
    'id' => $buildId,
    'path' => __DIR__,
];

spl_autoload_register(static function (string $class): void {
    $base = __DIR__ . '/src/';
    $prefix = 'AdapterKit\\Core\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) === 0) {
        $relative = substr($class, $len);
        if ($relative === '' || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\z/D', $relative) !== 1) {
            return;
        }

        $file = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
        if (is_file($file)) {
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
        if ($relative === '' || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\z/D', $relative) !== 1) {
            return;
        }

        $file = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
