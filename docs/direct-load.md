# Direct-Load Guide

WP Adapter supports loading without Composer at plugin runtime. This is the
standard distribution model for WordPress plugins.

## How it works

1. During development/build, install via Composer:
   ```bash
   composer require --dev devuri/wp-adapter
   ```

2. Run the copy binary from your plugin root:
   ```bash
   vendor/bin/wp-adapter-copy
   ```

   This copies `src/` and `psr-log/` into `lib/wp-adapter/` inside your plugin.

3. In your plugin's main file, load via `init.php`:
   ```php
   require_once __DIR__ . '/lib/wp-adapter/init.php';
   ```

4. Strip `vendor/` before distributing. `lib/` ships with the plugin.

## What gets copied

- `src/` - all WP Adapter classes including `src/Testing/`
- `psr-log/` - psr/log ^1.1 source (PHP 7.4-safe)
- `init.php` - the autoloader entry point
- `composer.json` - package metadata

Excluded: `tests/`, `vendor/`, `.git/`, `bin/`, `examples/`, build artifacts.

## PSR-3 in direct-load

`init.php` registers two autoloaders:
- `AdapterKit\Core\` from `src/`
- `Psr\Log\` from `psr-log/`

The `psr-log/` directory is populated from `vendor/psr/log/Psr/Log/` during the copy step.
It is pinned to `^1.1` to guarantee PHP 7.4 compatibility regardless of the build machine.

## No load-order cleverness

Do not use a `class_exists` guard:

```php
// WRONG - silently accepts the first loaded version
if (! class_exists(AdapterKit\Core\Core::class)) {
    require_once __DIR__ . '/lib/wp-adapter/init.php';
}
```

Load unconditionally. Namespace rewriting per plugin is deferred to a future build step.
