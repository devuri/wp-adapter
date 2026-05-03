# Compatibility

## PHP version support

| PHP | Status |
|---|---|
| 7.4 | Minimum supported. All code must run on 7.4. |
| 8.0, 8.1, 8.2 | Supported. |

## Forbidden PHP 8.0+ syntax

The following constructs are explicitly banned in `src/`:

| Construct | Reason |
|---|---|
| Constructor property promotion | PHP 8.0+ |
| Union types (`int\|string`) | PHP 8.0+ |
| `mixed` type hint | PHP 8.0+ |
| Nullsafe operator (`?->`) | PHP 8.0+ |
| Named arguments | PHP 8.0+ |
| `match` expression | PHP 8.0+ |
| `readonly` properties | PHP 8.1+ |
| `enum` | PHP 8.1+ |
| `${var}` dynamic property access | Deprecated in 8.2 |

For return types that would need `int|string`, use PHPDoc only:

```php
/**
 * @return int|string
 */
public function currentTime(string $type);
```

For logger methods, follow the `psr/log` v1.1 signature with an untyped `$level`:

```php
public function log($level, $message, array $context = array()): void
```

## PSR-3 and PHP 7.4

`psr/log` is pinned to `^1.1` in `composer.json`. This is intentional:

- `psr/log` v2 and v3 require PHP 8.0+.
- The `wp-adapter-copy` binary copies `vendor/psr/log/src/` into the plugin
  bundle at build time. If Composer resolved v2 or v3 on a PHP 8 build machine,
  the bundled code would be PHP 8-only, silently breaking plugins deployed to
  PHP 7.4 sites.
- Pinning to `^1.1` guarantees the bundled copy is always PHP 7.4-safe,
  regardless of the PHP version on the build machine.

## WordPress version support

No minimum WordPress version is enforced in code. The adapters call standard
WordPress functions (`add_action`, `get_option`, `wp_remote_post`, etc.) that
have been stable since WordPress 3.x. Plugins are responsible for declaring
their own `Requires at least` in their plugin headers.

## Composer vs direct-load

The package supports both distribution modes:

| Mode | How |
|---|---|
| Composer | `composer require --dev devuri/wp-adapter` + `vendor/bin/wp-adapter-copy` at build time |
| Direct load | `require_once __DIR__ . '/lib/wp-adapter/init.php';` — ships `lib/` not `vendor/` |

See `docs/direct-load.md` for the full direct-load workflow.
