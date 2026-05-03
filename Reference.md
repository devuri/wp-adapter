# WP Adapter - Complete Technical Reference

**Package:** `devuri/wp-adapter`  
**Root namespace:** `AdapterKit\Core`  
**PHP:** 7.4, 8.0, 8.1, 8.2  
**Runtime dependency:** `psr/log ^1.1` only  
**License:** MIT

This document is a complete technical reference. Every interface, class, method
signature, return type, and behavioral constraint is specified here. No source
file needs to be consulted to generate correct code using this package.

---

## The non-negotiable rule

WordPress function calls (`get_option`, `update_option`, `add_action`,
`add_filter`, `wp_remote_post`, `wp_remote_get`, `get_current_screen`, etc.)
belong only in:

1. WP Adapter production adapter classes (`WordPressHooks`, `WordPressOptionStorage`, `WordPressTransientStorage`, `WordPressEnvironment`, `WordPressHttpClient`).
2. Plugin bootstrap code (`plugin.php` or equivalent top-level file).
3. Explicit hook, REST, and admin boundary methods in the consuming plugin (thin methods that call one WordPress API then delegate to services).
4. `PluginContext::fromPluginFile()`, which is the only approved WP Adapter non-adapter exception.

Service classes, domain logic, repositories, validators, calculators, and
business workflows must not call WordPress directly.

A service class is correct when it can be instantiated in a plain PHP file with
no WordPress loaded. A service class is wrong when its constructor or any of its
methods reference a WordPress function, global, or class.

---

## PHP 7.4 syntax constraints

The following constructs are **forbidden** in any class that claims PHP 7.4
compatibility. Do not generate them.

| Forbidden | Reason |
|---|---|
| Constructor property promotion | PHP 8.0+ |
| Union types (`int\|string` in declarations) | PHP 8.0+ |
| `mixed` type hint | PHP 8.0+ |
| Nullsafe operator `?->` | PHP 8.0+ |
| Named arguments | PHP 8.0+ |
| `match` expression | PHP 8.0+ |
| `readonly` properties | PHP 8.1+ |
| `enum` | PHP 8.1+ |
| Fibers | PHP 8.1+ |

For return types that would need `int|string`, omit the PHP return type and use
PHPDoc only:
```php
/**
 * @return int|string
 */
public function currentTime(string $type)
```

---

## Loading

### Composer autoload (development)
```
Namespace prefix: AdapterKit\Core\
Maps to:          src/
```
Installed via `composer require --dev devuri/wp-adapter`. Also registers
`Psr\Log\` from `vendor/psr/log/Psr/Log/`.

### Direct load (plugin distribution)
```php
require_once __DIR__ . '/lib/wp-adapter/init.php';
```
`init.php` registers two `spl_autoload_register` callbacks:
- `AdapterKit\Core\` → `<init-dir>/src/`
- `Psr\Log\` → `<init-dir>/psr-log/`

The `psr-log/` directory must contain `LoggerInterface.php`, `AbstractLogger.php`,
`NullLogger.php`, `LogLevel.php`, and the other PSR-3 files placed flat (no
subdirectory) by the `wp-adapter-copy` binary. The copy source in psr/log v1.1.x
is `vendor/psr/log/Psr/Log/`. Do not wrap the `require_once` in a `class_exists`
guard.

### Build-time copy binary
```bash
vendor/bin/wp-adapter-copy
```
Copies `src/`, `init.php`, `composer.json`, and `psr-log/` into
`./lib/wp-adapter/` inside the consuming plugin. Run from the plugin root after
`composer install`. The plugin ships `lib/wp-adapter/`; `vendor/` is stripped
before distribution.

---

## Contracts (interfaces)

All interfaces are in `AdapterKit\Core\Contracts\`. All are PHP 7.4 compatible.
Plugin service classes must type-hint against these interfaces, never against
the concrete adapter classes.

---

### `HooksInterface`

```php
namespace AdapterKit\Core\Contracts;

interface HooksInterface
{
    public function addAction(
        string   $tag,
        callable $callback,
        int      $priority = 10,
        int      $args     = 1
    ): void;

    public function addFilter(
        string   $tag,
        callable $callback,
        int      $priority = 10,
        int      $args     = 1
    ): void;

    public function registerRestRoute(
        string $namespace,
        string $route,
        array  $args
    ): bool;
}
```

`addAction` and `addFilter` return `void` - there is no meaningful success
signal from hook registration. `registerRestRoute` returns `bool` matching
WordPress's `register_rest_route` return value.

---

### `OptionStorageInterface`

```php
namespace AdapterKit\Core\Contracts;

interface OptionStorageInterface
{
    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = false);

    /**
     * @param mixed $value
     */
    public function update(string $key, $value, ?bool $autoload = null): bool;

    public function delete(string $key): bool;
}
```

`get` returns the stored value or `$default` (default `false`) when the key
does not exist. `update` always returns `true` in the testing adapter. `delete`
always returns `true`. Do not treat a `false` return from
`WordPressOptionStorage::update()` as always meaning failure; WordPress returns
`false` when the stored value did not change, not only on error.

---

### `TransientStorageInterface`

```php
namespace AdapterKit\Core\Contracts;

interface TransientStorageInterface
{
    /**
     * @return mixed  Stored value, or false if key missing or expired.
     */
    public function get(string $key);

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, int $expiration): bool;

    public function delete(string $key): bool;
}
```

`expiration = 0` means no expiration (matches WordPress `set_transient`
behaviour). `get` returns `false` when the key is missing or expired. Both
`set` and `delete` return `true`.

---

### `EnvironmentInterface`

```php
namespace AdapterKit\Core\Contracts;

interface EnvironmentInterface
{
    public function homeUrl(string $path = ''): string;

    public function adminUrl(string $path = ''): string;

    /**
     * @return int|string
     */
    public function currentTime(string $type);

    public function sanitizeTextField(string $value): string;

    public function sanitizeKey(string $key): string;

    public function escHtml(string $value): string;

    public function escUrl(string $url): string;

    public function escAttr(string $value): string;

    public function wpKsesPost(string $value): string;

    public function getCurrentScreenId(): ?string;
}
```

`currentTime` has no PHP return type declaration (PHP 7.4 cannot express
`int|string`). Callers must handle both an `int` (when `$type` is `'timestamp'`
or `'U'`) and a `string` (all other types). `getCurrentScreenId` returns `null`
when outside the admin, when `get_current_screen` is not available, or when no
screen is active.

---

### `HttpClientInterface`

```php
namespace AdapterKit\Core\Contracts;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function get(string $url, array $args = []): array;

    /**
     * @param array<string, mixed> $args
     * @return array{is_error: bool, error_message: string|null, code: int, body: string}
     */
    public function post(string $url, array $args = []): array;
}
```

Both methods always return an array with exactly four keys:

| Key | Type | Meaning |
|---|---|---|
| `is_error` | `bool` | `true` if the request failed (network error, WP_Error) |
| `error_message` | `string\|null` | Error description when `is_error` is `true`; `null` otherwise |
| `code` | `int` | HTTP status code; `0` on network error |
| `body` | `string` | Raw response body; empty string on error |

Always check `is_error` before reading `code` or `body`.

---

### `ClockInterface`

```php
namespace AdapterKit\Core\Contracts;

interface ClockInterface
{
    public function now(): int;
}
```

Returns a Unix timestamp as an integer.

---

### `LoggerInterface` (PSR-3)

Not a package-owned file. Use `Psr\Log\LoggerInterface` directly.

```php
use Psr\Log\LoggerInterface;
```

PSR-3 log levels (string constants on `Psr\Log\LogLevel`):
`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`.

Use `Psr\Log\LoggerInterface` for all service constructor type hints. Use
`AdapterKit\Core\Logging\NullLogger` or `WordPressDebugLogger` only at the
plugin bootstrap/wiring edge.

---

## WordPress adapters (production)

All five classes are `final`. Pass these to plugin constructors in production
(`plugin.php`). Do not use them inside service classes.

| Class | Implements | Namespace |
|---|---|---|
| `WordPressHooks` | `HooksInterface` | `AdapterKit\Core\Hooks` |
| `WordPressOptionStorage` | `OptionStorageInterface` | `AdapterKit\Core\Storage` |
| `WordPressTransientStorage` | `TransientStorageInterface` | `AdapterKit\Core\Storage` |
| `WordPressEnvironment` | `EnvironmentInterface` | `AdapterKit\Core\Environment` |
| `WordPressHttpClient` | `HttpClientInterface` | `AdapterKit\Core\Http` |

In v0.1, all five have a zero-argument constructor:
```php
new WordPressHooks()
new WordPressOptionStorage()
new WordPressTransientStorage()
new WordPressEnvironment()
new WordPressHttpClient()
```

`WordPressEnvironment::getCurrentScreenId()` guards `function_exists('get_current_screen')`
and returns `null` if the function is not available.

---

## Testing adapters

All six classes are `final`. Use these in unit tests. None require WordPress.
All are in namespace `AdapterKit\Core\Testing`.

---

### `InMemoryOptionStorage`

```php
final class InMemoryOptionStorage implements OptionStorageInterface
{
    public function __construct(array $initial = []) {}

    // OptionStorageInterface
    public function get(string $key, $default = false);  // @return mixed
    public function update(string $key, $value, ?bool $autoload = null): bool;
    public function delete(string $key): bool;

    // Extra inspection methods (not on the interface)
    public function has(string $key): bool;
    public function all(): array;
    public function clear(): void;
}
```

`$initial` is a key→value map pre-populating the store. `has` checks key
existence. `all` returns the full store contents. `clear` resets to empty.

---

### `InMemoryTransientStorage`

```php
final class InMemoryTransientStorage implements TransientStorageInterface
{
    public function __construct(ClockInterface $clock) {}

    // TransientStorageInterface
    public function get(string $key);          // @return mixed
    public function set(string $key, $value, int $expiration): bool;
    public function delete(string $key): bool;
}
```

`$expiration = 0` stores the entry with no expiry - `get` will never expire it
regardless of how much the clock advances. Any positive `$expiration` is stored
as `now() + $expiration`; `get` returns `false` when `now() >= expires_at`.
The same `ClockInterface` instance must be shared with `FrozenClock` in tests
so that `advance()` is visible to the storage.

---

### `MockHttpClient`

```php
final class MockHttpClient implements HttpClientInterface
{
    public function __construct() {}

    // Setup methods (call before the code under test runs)
    public function addJsonResponse(string $urlFragment, array $body, int $code = 200): void;
    public function addErrorResponse(string $urlFragment, string $errorMessage): void;
    public function addRawResponse(string $urlFragment, array $response): void;

    // HttpClientInterface
    public function get(string $url, array $args = []): array;
    public function post(string $url, array $args = []): array;

    // Inspection methods (call in assertions)
    public function wasRequestMadeTo(string $urlFragment): bool;
    public function getLastRequest(): ?array;    // null if no requests made
    public function getRequestCount(): int;
    public function getRequestHistory(): array;

    // Reset
    public function clear(): void;
}
```

URL matching is done with `strpos($url, $urlFragment) !== false` - a partial
match. Responses are checked in insertion order. Register responses before the
code under test executes. If no response is registered for a URL, `get`/`post`
return an error response with message `"No mock response registered for: <url>"`.

`getLastRequest()` returns `['method' => string, 'url' => string, 'args' => array]`
or `null`. `getRequestHistory()` returns an array of the same shape.

`addJsonResponse` runs `json_encode($body)`; if encoding fails, `body` is `''`.

---

### `RecordingHooks`

```php
final class RecordingHooks implements HooksInterface
{
    public function __construct() {}

    // HooksInterface
    public function addAction(string $tag, callable $callback, int $priority = 10, int $args = 1): void;
    public function addFilter(string $tag, callable $callback, int $priority = 10, int $args = 1): void;
    public function registerRestRoute(string $namespace, string $route, array $args): bool;

    // Inspection methods
    public function hasAction(string $tag): bool;
    public function hasFilter(string $tag): bool;
    public function hasRestRoute(string $route): bool;
    public function getActions(): array;   // array of {tag, callback, priority, args}
    public function getFilters(): array;   // array of {tag, callback, priority, args}
    public function getRestRoutes(): array; // array of {namespace, route, args}

    // Reset
    public function clear(): void;
}
```

`hasRestRoute` matches against the `$route` parameter (the second argument to
`registerRestRoute`), not the namespace. If two routes share the same path under
different namespaces, use `getRestRoutes()` for precise assertions.

---

### `RecordingLogger`

```php
final class RecordingLogger extends AbstractLogger  // Psr\Log\AbstractLogger
{
    public function __construct() {}

    // AbstractLogger implementation
    public function log($level, $message, array $context = []): void;

    // Inspection methods
    public function hasWarning(string $message): bool;
    public function hasError(string $message): bool;
    public function hasInfo(string $message): bool;
    public function hasDebug(string $message): bool;
    public function getErrors(): array;    // entries with level 'error'
    public function getWarnings(): array;  // entries with level 'warning'
    public function all(): array;          // all entries regardless of level
    public function count(string $level): int;

    // Reset
    public function clear(): void;
}
```

The `has*` methods match using `strpos($entry['message'], $message) !== false` -
they return `true` if the logged message **contains** the given string, not only
if it matches exactly. Entries are stored as
`['level' => string, 'message' => string, 'context' => array]`.

---

### `MockEnvironment`

```php
final class MockEnvironment implements EnvironmentInterface
{
    public function __construct(string $homeUrl, string $adminUrl, int $timestamp) {}

    // EnvironmentInterface
    public function homeUrl(string $path = ''): string;
    public function adminUrl(string $path = ''): string;
    public function currentTime(string $type);  // @return int|string
    public function sanitizeTextField(string $value): string;
    public function sanitizeKey(string $key): string;
    public function escHtml(string $value): string;
    public function escUrl(string $url): string;
    public function escAttr(string $value): string;
    public function wpKsesPost(string $value): string;
    public function getCurrentScreenId(): ?string;

    // Mutation
    public function setCurrentScreenId(?string $id): void;
}
```

`homeUrl('')` returns the base URL with no trailing slash. `homeUrl('pricing')`
returns `{base}/pricing`. `adminUrl` behaves identically.

`currentTime` behaviour by `$type`:
- `'timestamp'` or `'U'` → returns `int` (the constructor timestamp)
- `'mysql'` → returns `string` in `'Y-m-d H:i:s'` format
- any other value → returns `date($type, $timestamp)`

`sanitizeTextField` applies `trim(strip_tags($value))`.  
`sanitizeKey` lowercases and strips all characters except `[a-z0-9_\-]`.  
`escHtml`, `escUrl`, `escAttr` all apply `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.  
`wpKsesPost` applies `strip_tags` allowing `p a strong em ul ol li br hr`.  
`getCurrentScreenId` returns whatever was set via `setCurrentScreenId`, defaulting to `null`.

---

## Value types

### `PluginContext`

```php
namespace AdapterKit\Core;

final class PluginContext
{
    // Factories
    public static function fromPluginFile(
        string $file,
        string $slug,
        string $version,
        string $textDomain,
        string $optionPrefix
    ): self;   // calls plugin_basename(), plugin_dir_path(), plugin_dir_url()

    public static function fromValues(
        string $slug,
        string $version,
        string $file,
        string $basename,
        string $dirPath,
        string $dirUrl,
        string $textDomain,
        string $optionPrefix
    ): self;   // pure, no WordPress calls

    // Getters
    public function getSlug(): string;
    public function getVersion(): string;
    public function getFile(): string;        // absolute path to plugin main file
    public function getBasename(): string;    // e.g. 'my-plugin/my-plugin.php'
    public function getDirPath(): string;     // absolute path with trailing slash
    public function getDirUrl(): string;      // URL with trailing slash
    public function getTextDomain(): string;
    public function getOptionPrefix(): string;
}
```

`fromPluginFile` calls three WordPress functions and is the only approved
exception to the "no WordPress in non-adapters" rule. Use `fromValues` in unit
tests. `fromPluginFile` is only valid inside a WordPress bootstrap context.

---

### `Result`

```php
namespace AdapterKit\Core;

final class Result
{
    public static function success(array $data = []): self;
    public static function failure(string $code, string $message, array $data = []): self;

    public function isSuccess(): bool;
    public function getCode(): string;    // 'success' for success results
    public function getMessage(): string; // '' for success results
    public function getData(): array;
}
```

Use `Result` as the return type of service methods. Check `isSuccess()` before
reading `getCode()` or `getMessage()`. `getData()` is available on both success
and failure results.

---

### `KeyBuilder`

```php
namespace AdapterKit\Core\Support;

final class KeyBuilder
{
    public function __construct(string $prefix) {}

    public function option(string $name): string;    // {prefix}_{name}
    public function transient(string $name): string; // {prefix}_{name}
    public function hook(string $name): string;      // {prefix}/{name}
    public function cache(string $name): string;     // {prefix}_{name}
}
```

`option`, `transient`, and `cache` all use underscore as separator.
`hook` uses forward slash. Pass the plugin's `optionPrefix` from `PluginContext`.

---

## Time

### `SystemClock`

```php
namespace AdapterKit\Core\Time;

final class SystemClock implements ClockInterface
{
    public function __construct() {}
    public function now(): int;  // returns time()
}
```

Use in production. No parameters.

---

### `FrozenClock`

```php
namespace AdapterKit\Core\Time;

final class FrozenClock implements ClockInterface
{
    public function __construct(int $ts) {}

    public function now(): int;
    public function advance(int $seconds): void;  // mutates the clock in place
}
```

Use in tests. Pass the same `FrozenClock` instance to `InMemoryTransientStorage`
so that `advance()` affects expiry checks. Calling `advance(61)` after
`set('key', $value, 60)` will make `get('key')` return `false`.

---

## Loggers

### `NullLogger`

```php
namespace AdapterKit\Core\Logging;

final class NullLogger extends \Psr\Log\NullLogger {}
```

Discards all log calls. Use in production when logging is not needed.

---

### `WordPressDebugLogger`

```php
namespace AdapterKit\Core\Logging;

final class WordPressDebugLogger extends \Psr\Log\AbstractLogger
{
    public function __construct(string $minimumLevel = \Psr\Log\LogLevel::DEBUG) {}

    public function log($level, $message, array $context = []): void;
}
```

Writes to `error_log()` only when `WP_DEBUG_LOG` is defined and truthy.
Unknown `$level` values are normalized to `LogLevel::DEBUG`. Unknown
`$minimumLevel` constructor values are normalized to `LogLevel::DEBUG`.
Context placeholders `{key}` in `$message` are replaced with `$context['key']`
if the value is a non-array scalar or stringable object.

---

### `RecordingLogger`

Documented in the Testing Adapters section above.

---

## Canonical patterns

### Plugin bootstrap (`plugin.php`)

```php
<?php
/**
 * Plugin Name: My Plugin
 * Version:     1.0.0
 * Text Domain: my-plugin
 */

declare(strict_types=1);

use AdapterKit\Core\PluginContext;
use AdapterKit\Core\Hooks\WordPressHooks;
use AdapterKit\Core\Storage\WordPressOptionStorage;
use AdapterKit\Core\Storage\WordPressTransientStorage;
use AdapterKit\Core\Http\WordPressHttpClient;
use AdapterKit\Core\Logging\NullLogger;

require_once __DIR__ . '/lib/wp-adapter/init.php';

$plugin = new MyPlugin\Plugin(
    PluginContext::fromPluginFile(__FILE__, 'my-plugin', '1.0.0', 'my-plugin', 'myplugin_'),
    new WordPressHooks(),
    new WordPressOptionStorage(),
    new WordPressTransientStorage(),
    new WordPressHttpClient(),
    new NullLogger()
);

$plugin->register();
```

---

### Service class (correct structure)

```php
<?php

declare(strict_types=1);

namespace MyPlugin;

use AdapterKit\Core\Contracts\OptionStorageInterface;
use AdapterKit\Core\Contracts\HttpClientInterface;
use AdapterKit\Core\Result;
use Psr\Log\LoggerInterface;

final class LicenseService
{
    private OptionStorageInterface $options;
    private HttpClientInterface    $http;
    private LoggerInterface        $logger;
    private string                 $optionKey;

    public function __construct(
        OptionStorageInterface $options,
        HttpClientInterface    $http,
        LoggerInterface        $logger,
        string                 $optionKey
    ) {
        $this->options   = $options;
        $this->http      = $http;
        $this->logger    = $logger;
        $this->optionKey = $optionKey;
    }

    public function activate(string $key): Result
    {
        $response = $this->http->post('https://api.example.com/activate', [
            'body' => ['key' => $key],
        ]);

        if ($response['is_error']) {
            $this->logger->warning('activation_failed', [
                'reason' => $response['error_message'],
            ]);
            return Result::failure('activation_failed', (string) $response['error_message']);
        }

        $this->options->update($this->optionKey, ['active' => true, 'key' => $key]);
        return Result::success(['active' => true]);
    }

    public function isActive(): bool
    {
        $stored = $this->options->get($this->optionKey, []);
        return is_array($stored) && !empty($stored['active']);
    }
}
```

No WordPress functions. No `use` statements importing WordPress classes.
Dependencies enter only through the constructor typed against interfaces.

---

### Plugin class (correct structure)

```php
<?php

declare(strict_types=1);

namespace MyPlugin;

use AdapterKit\Core\Contracts\HooksInterface;
use AdapterKit\Core\Contracts\HttpClientInterface;
use AdapterKit\Core\Contracts\OptionStorageInterface;
use AdapterKit\Core\PluginContext;
use Psr\Log\LoggerInterface;

final class Plugin
{
    private PluginContext  $context;
    private HooksInterface $hooks;
    private LicenseService $license;

    public function __construct(
        PluginContext          $context,
        HooksInterface         $hooks,
        OptionStorageInterface $options,
        HttpClientInterface    $http,
        LoggerInterface        $logger
    ) {
        $this->context = $context;
        $this->hooks   = $hooks;
        $this->license = new LicenseService(
            $options,
            $http,
            $logger,
            $context->getOptionPrefix() . 'license'
        );
    }

    public function register(): void
    {
        $this->hooks->addAction('admin_menu', [$this, 'registerAdminMenu']);
        $this->hooks->registerRestRoute('my-plugin/v1', '/license/activate', [
            'methods'  => 'POST',
            'callback' => [$this->license, 'activate'],
        ]);
    }

    public function registerAdminMenu(): void
    {
        // Thin WordPress boundary method only. It may call add_menu_page(),
        // then delegate real behavior to services. Do not put business logic here.
    }
}
```

---

### PHPUnit config (`phpunit.xml.dist`)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         defaultTestSuite="Unit"
         colors="true"
         verbose="false">

    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>

</phpunit>
```

`defaultTestSuite="Unit"` means `vendor/bin/phpunit` with no arguments runs
only unit tests. Integration tests require explicit invocation.

---

### Unit test bootstrap (`tests/bootstrap.php`)

```php
<?php

// WordPress is NOT loaded.
require_once dirname(__DIR__) . '/vendor/autoload.php';
```

This single line is sufficient. Composer's autoloader provides all of
`AdapterKit\Core\*` and `Psr\Log\*`. No `WP_TESTS_DIR`. No WordPress functions.

---

### Unit test class

```php
<?php

declare(strict_types=1);

namespace MyPlugin\Tests\Unit;

use MyPlugin\LicenseService;
use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\InMemoryOptionStorage;
use AdapterKit\Core\Testing\MockHttpClient;
use AdapterKit\Core\Testing\RecordingLogger;

final class LicenseServiceTest extends TestCase
{
    private InMemoryOptionStorage $options;
    private MockHttpClient        $http;
    private RecordingLogger       $logger;
    private LicenseService        $service;

    protected function setUp(): void
    {
        $this->options = new InMemoryOptionStorage(['myplugin_license' => []]);
        $this->http    = new MockHttpClient();
        $this->logger  = new RecordingLogger();
        $this->service = new LicenseService(
            $this->options,
            $this->http,
            $this->logger,
            'myplugin_license'
        );
    }

    public function test_activate_stores_key_on_success(): void
    {
        $this->http->addJsonResponse('/activate', ['ok' => true], 200);

        $result = $this->service->activate('VALID-KEY-123');

        $this->assertTrue($result->isSuccess());
        $stored = $this->options->get('myplugin_license');
        $this->assertTrue($stored['active']);
        $this->assertSame('VALID-KEY-123', $stored['key']);
    }

    public function test_activate_returns_failure_on_http_error(): void
    {
        $this->http->addErrorResponse('/activate', 'Connection refused.');

        $result = $this->service->activate('ANY-KEY');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('activation_failed', $result->getCode());
        $this->assertTrue($this->logger->hasWarning('activation_failed'));
    }
}
```

---

### Integration test class

```php
<?php

declare(strict_types=1);

namespace MyPlugin\Tests\Integration;

use WP_UnitTestCase;

/**
 * @group integration
 */
final class LicenseOptionStorageTest extends WP_UnitTestCase
{
    public function test_option_written_by_adapter_is_readable_by_wordpress(): void
    {
        // Tests that WordPressOptionStorage interacts correctly with the WP options table.
    }
}
```

Every integration test class must be annotated `@group integration`. Run with:
```bash
WP_TESTS_DIR=/path/to/wordpress-tests-lib vendor/bin/phpunit --testsuite Integration
```

Integration tests must not appear in `tests/Unit/`. Unit tests must not extend
`WP_UnitTestCase` or call any WordPress function.

---

## Explicit anti-patterns

Do not generate any of the following.

**WordPress function call inside a service:**
```php
// Wrong
public function activate(string $key): Result
{
    $settings = get_option('myplugin_settings'); // forbidden outside adapter
}
```

**Concrete adapter in a service constructor:**
```php
// Wrong - ties the service to WordPress even in tests
public function __construct(WordPressOptionStorage $options) {}

// Correct
public function __construct(OptionStorageInterface $options) {}
```

**`class_exists` guard on init.php:**
```php
// Wrong - silently accepts a different (possibly incompatible) version
if (! class_exists(\AdapterKit\Core\Result::class)) {
    require_once __DIR__ . '/lib/wp-adapter/init.php';
}
```

**Extending a concrete adapter:**
```php
// Wrong - all concrete adapters are final
class MyStorage extends WordPressOptionStorage {}
```

**`mixed` type hint anywhere in plugin code targeting PHP 7.4:**
```php
// Wrong
public function process(mixed $value): mixed {}
```

**Extending `WP_UnitTestCase` in a unit test:**
```php
// Wrong - unit tests must not load WordPress
final class MyServiceTest extends WP_UnitTestCase {}
```

**Registering responses after the code runs:**
```php
// Wrong - responses must be registered before the call
$service->activate('KEY');
$http->addJsonResponse('/activate', ['ok' => true]);
```

---

## Disambiguation

**`RecordingLogger::has*` matching is substring-based**, not exact. `hasWarning('failed')` returns `true` if any warning message contains the string `'failed'` anywhere.

**`MockHttpClient` URL matching is also substring-based**. `addJsonResponse('/activate', ...)` matches any URL containing `/activate`. Register more specific fragments first if multiple responses share a substring.

**`InMemoryOptionStorage::get($key, $default)` default is `false`**, not `null`. Checking `if ($value)` will be `false` for both missing keys and stored falsy values. Use `has($key)` to distinguish.

**`FrozenClock::advance()` mutates the shared instance.** Both the test and the `InMemoryTransientStorage` holding a reference to the same clock will see the advanced time. This is the intended usage.

**`PluginContext::fromValues` parameter order** differs from `fromPluginFile`. `fromPluginFile` takes `($file, $slug, $version, $textDomain, $optionPrefix)`. `fromValues` takes `($slug, $version, $file, $basename, $dirPath, $dirUrl, $textDomain, $optionPrefix)`.

**`KeyBuilder::option` and `::transient` produce the same format** (`prefix_name`). They exist as separate methods for intent clarity, not because the format differs.

**`registerRestRoute` on `RecordingHooks` always returns `true`.** On `WordPressHooks` it returns whatever `register_rest_route` returns, which can be `false` if called outside the `rest_api_init` hook.

**`Result::getCode()` on a success result returns `'success'`**, not an empty string. `Result::getMessage()` on a success result returns `''`.
