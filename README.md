# WP Adapter

WordPress contracts, production adapters, and ready-made testing adapters for clean, testable plugin development.

```bash
composer require --dev devuri/wp-adapter
```

[Quick start](#quick-start) | [How it works](#how-it-works) | [Contracts](#contracts-and-implementations) | [Testing adapters](#testing-adapter-reference) | [Direct-load distribution](#direct-load-distribution) | [Advanced usage](#advanced-usage)

## Quick start

This is the shortest path from installation to a service that runs with WordPress in production and without WordPress in unit tests.

### 1. Install for development

```bash
composer require --dev devuri/wp-adapter
```

Composer makes the contracts, production adapters, testing adapters, and `psr/log` available during development.

### 2. Write plugin logic against a contract

```php
use AdapterKit\Core\Contracts\OptionStorageInterface;

final class SettingsService
{
    private OptionStorageInterface $options;

    public function __construct(OptionStorageInterface $options)
    {
        $this->options = $options;
    }

    public function enable(): void
    {
        $this->options->update(
            'myplugin_settings',
            ['enabled' => true]
        );
    }
}
```

`myplugin_settings` is the WordPress option name. The array is simply the value this service chooses to store. `update()` accepts any value supported by WordPress options. See [Option storage contract](#option-storage-contract) for the full behavior.

### 3. Use the WordPress adapter in production

Create concrete adapters in the plugin bootstrap, then pass them into services:

```php
use AdapterKit\Core\Storage\WordPressOptionStorage;

$settings = new SettingsService(new WordPressOptionStorage());
```

Pass `$settings` to the plugin class or controller that needs it. The service never calls `get_option()` or `update_option()` directly.

### 4. Use the testing adapter in a unit test

```php
use AdapterKit\Core\Testing\InMemoryOptionStorage;
use PHPUnit\Framework\TestCase;

final class SettingsServiceTest extends TestCase
{
    public function test_enable_stores_the_setting(): void
    {
        $options = new InMemoryOptionStorage();
        $settings = new SettingsService($options);

        $settings->enable();

        $this->assertSame(
            ['enabled' => true],
            $options->get('myplugin_settings')
        );
    }
}
```

The same service runs without loading WordPress. The package supplies the testing adapter, so the test does not need a custom mock.

### 5. Build a distributable plugin

Run this from the plugin root:

```bash
vendor/bin/wp-adapter-copy
```

Load the copied bundle from the plugin's main file:

```php
require_once __DIR__ . '/lib/wp-adapter/init.php';
```

Ship `lib/wp-adapter/` in the plugin ZIP and remove `vendor/`. See [Direct-load distribution](#direct-load-distribution) for build safety and conflict handling.

## How it works

WP Adapter creates one boundary between plugin business logic and common WordPress APIs.

| Layer | Main namespaces | Purpose |
|---|---|---|
| Contracts | `AdapterKit\Core\Contracts\` | Interfaces used by plugin business logic |
| WordPress adapters | `Storage`, `Http`, `Hooks`, and `Environment` under `AdapterKit\Core\` | Production implementations that call WordPress |
| Testing adapters | `AdapterKit\Core\Testing\`, plus `Time\FrozenClock` | Controlled implementations used in unit tests |

```text
                         Plugin business logic
                                  |
                                  v
                         Contract interfaces
                           /             \
                          /               \
               WordPress adapters     Testing adapters
                  in production        in unit tests
                          |
                          v
                    WordPress APIs
```

A service depends only on a contract. The plugin bootstrap chooses the production implementation, while the unit test chooses the testing implementation. All three layers are maintained as versioned package API.

The production adapters are intentionally thin wrappers around WordPress. The testing adapters provide controlled state, configured responses, and recorded calls for service-level tests. They are not intended to reproduce every WordPress runtime edge case. Use integration tests when exact WordPress behavior is what the test needs to prove.

## What this solves

WordPress plugins often call `get_option()`, `add_action()`, and `wp_remote_post()` directly inside business logic. That couples the logic to a running WordPress installation and makes isolated tests difficult.

WP Adapter keeps WordPress at the edge. Services receive small interfaces through their constructors and remain plain PHP. In production they receive WordPress adapters. In unit tests they receive the supplied testing adapters. No custom mocks and no WordPress bootstrap are needed for those service tests.

## The boundary rule

WordPress function calls belong only in the production adapter classes and the plugin bootstrap edge.

Business logic must call contracts instead of calling WordPress directly:

```php
// Wrong: the service now requires WordPress.
$value = get_option('myplugin_settings', []);

// Right: the service depends on a contract.
$value = $this->options->get('myplugin_settings', []);
```

The package cannot isolate a service that still calls `get_option()`, `wp_remote_post()`, `add_action()`, or another WordPress function internally.

`PluginContext::fromPluginFile()` is the documented bootstrap-edge exception. It calls WordPress path helpers to construct immutable plugin metadata. Use `PluginContext::fromValues()` when WordPress is not loaded.

A useful boundary check is simple: instantiate the service from a plain PHP process that loads only Composer. If that triggers a missing WordPress function, WordPress has leaked past the edge.

See [docs/testing-guide.md](docs/testing-guide.md) for a complete wrong-versus-right example and checklist.

## Production wiring

A real plugin usually creates several adapters in one bootstrap location and passes them into the main plugin object:

```php
use AdapterKit\Core\Http\WordPressHttpClient;
use AdapterKit\Core\Hooks\WordPressHooks;
use AdapterKit\Core\Logging\NullLogger;
use AdapterKit\Core\PluginContext;
use AdapterKit\Core\Storage\WordPressOptionStorage;
use AdapterKit\Core\Storage\WordPressTransientStorage;

$context = PluginContext::fromPluginFile(
    __FILE__,
    'my-plugin',
    '1.0.0',
    'my-plugin',
    'myplugin_'
);

$plugin = new MyPlugin\Plugin(
    $context,
    new WordPressHooks(),
    new WordPressOptionStorage(),
    new WordPressTransientStorage(),
    new WordPressHttpClient(),
    new NullLogger()
);

$plugin->register();
```

Run this from the main plugin file, or pass the main plugin file path explicitly. `PluginContext::fromPluginFile()` uses that path to calculate the plugin basename, directory path, and directory URL.

This bootstrap is the composition root: the place that selects concrete implementations and passes them into the plugin. Business logic should continue to type-hint contracts, not these concrete classes.

## Complete service example

The following service combines option storage, HTTP, logging, and `Result` while remaining independent of WordPress:

```php
use AdapterKit\Core\Contracts\HttpClientInterface;
use AdapterKit\Core\Contracts\OptionStorageInterface;
use AdapterKit\Core\Result;
use Psr\Log\LoggerInterface;

final class LicenseService
{
    private OptionStorageInterface $options;
    private HttpClientInterface $http;
    private LoggerInterface $logger;
    private string $optionKey;

    public function __construct(
        OptionStorageInterface $options,
        HttpClientInterface $http,
        LoggerInterface $logger,
        string $optionKey
    ) {
        $this->options = $options;
        $this->http = $http;
        $this->logger = $logger;
        $this->optionKey = $optionKey;
    }

    public function activate(string $key): Result
    {
        $response = $this->http->post(
            'https://api.example.com/activate',
            ['body' => ['key' => $key]]
        );

        if ($response['is_error']) {
            $message = $response['error_message'] ?? 'Activation request failed.';
            $this->logger->warning('activation_failed', ['reason' => $message]);

            return Result::failure('activation_failed', $message);
        }

        if ($response['code'] < 200 || $response['code'] >= 300) {
            return Result::failure(
                'activation_rejected',
                'The activation server rejected the request.'
            );
        }

        $payload = json_decode($response['body'], true);

        if (!is_array($payload) || empty($payload['ok'])) {
            return Result::failure(
                'invalid_response',
                'The activation server returned an invalid response.'
            );
        }

        $this->options->update($this->optionKey, [
            'active' => true,
            'key' => $key,
        ]);

        return Result::success(['active' => true]);
    }
}
```

The HTTP contract returns a normalized array with `is_error`, `error_message`, `code`, and `body`. The body is a string, so the service decodes JSON itself. A 400 or 500 response is not a transport error, which is why the example checks the status code separately.

The option value is an array because this service stores two related fields. The storage contract does not require arrays. See the contract reference below for accepted values and WordPress return semantics.

## Contracts and implementations

The examples in this section show valid uses, not the only accepted values. For example, an option may be an array, string, number, boolean, object, or another value WordPress can store. Each subsection describes the actual contract and any important differences between the production and testing implementations.

Plugin services depend on six package-owned contracts plus PSR-3 logging.

| Concern | Contract | Production implementation | Testing implementation |
|---|---|---|---|
| Hooks and REST routes | `HooksInterface` | `WordPressHooks` | `RecordingHooks` |
| Options | `OptionStorageInterface` | `WordPressOptionStorage` | `InMemoryOptionStorage` |
| Transients | `TransientStorageInterface` | `WordPressTransientStorage` | `InMemoryTransientStorage` |
| URLs, sanitization, escaping, screen state | `EnvironmentInterface` | `WordPressEnvironment` | `MockEnvironment` |
| HTTP GET and POST | `HttpClientInterface` | `WordPressHttpClient` | `MockHttpClient` |
| Time | `ClockInterface` | `SystemClock` | `FrozenClock` |
| Logging | `Psr\Log\LoggerInterface` | `NullLogger` or `WordPressDebugLogger` | `RecordingLogger` |

### What thin wrapper means

The production storage and hook adapters mostly pass arguments directly to the equivalent WordPress functions:

| Adapter method | WordPress function |
|---|---|
| `OptionStorageInterface::get()` | `get_option()` |
| `OptionStorageInterface::update()` | `update_option()` |
| `OptionStorageInterface::delete()` | `delete_option()` |
| `TransientStorageInterface::get()` | `get_transient()` |
| `TransientStorageInterface::set()` | `set_transient()` |
| `TransientStorageInterface::delete()` | `delete_transient()` |
| `HooksInterface::addAction()` | `add_action()` |
| `HooksInterface::addFilter()` | `add_filter()` |
| `HooksInterface::registerRestRoute()` | `register_rest_route()` |

The adapter does not turn WordPress storage into an array-only API and it does not hide WordPress return semantics. The contract provides a replaceable boundary, not a new database abstraction.

### Option storage contract

```php
$value = $options->get('myplugin_settings', []);
$options->update('myplugin_settings', ['enabled' => true]);
$options->delete('myplugin_settings');
```

- `get($key, $default)` returns the stored value or the supplied default when the option is missing.
- `update($key, $value, $autoload)` accepts any value supported by WordPress options. Non-scalar values must be serializable.
- `delete($key)` removes the option.
- The default for `get()` is `false`. Pass an explicit default when a missing option must be distinguished from a legitimate false-like value.
- WordPress may return database-stored scalar values as strings. Arrays and objects are serialized and restored. `InMemoryOptionStorage` keeps the exact PHP value that was assigned.
- Production `update()` returns the result of `update_option()`. A `false` result can mean the value was unchanged or the update failed. `InMemoryOptionStorage::update()` always stores the value and returns `true`.
- Production `delete()` returns the result of `delete_option()`. The in-memory testing adapter removes the key and returns `true`, including when the key was already absent.

The last two differences are intentional. The in-memory adapter is optimized for testing service state, not for reproducing every storage-engine return edge case. Add an integration test when your code depends on exact WordPress return behavior.

### Transient storage contract

```php
$transients->set('myplugin_token', 'abc123', 60);
$value = $transients->get('myplugin_token');
$transients->delete('myplugin_token');
```

The value may be any WordPress-compatible transient value. The expiration is a number of seconds. Use `0` for no expiration or a positive integer for a maximum lifetime. Avoid negative expiration values because the in-memory adapter treats every non-positive value as no expiration, while WordPress or an external cache may handle a negative value differently. `get()` returns `false` when the transient is missing or expired, so storing boolean `false` makes those states indistinguishable.

A WordPress transient may disappear before its expiration time. Treat the expiration as a maximum lifetime, not a guarantee that the value will remain available. `InMemoryTransientStorage` uses an injected `ClockInterface` and expires an entry when `now()` is equal to or later than its calculated expiration time. It does not simulate early cache eviction, multisite behavior, external object caches, or transient key length limits.

### HTTP contract

Both `get()` and `post()` accept a URL and an optional WordPress HTTP arguments array. They return the same shape:

```php
[
    'is_error' => false,
    'error_message' => null,
    'code' => 200,
    'body' => '{"ok":true}',
]
```

- `is_error` is `true` when WordPress returns `WP_Error`.
- `error_message` contains the WordPress error message or `null`.
- `code` is the HTTP status code, or `0` for a transport error.
- `body` is always a string. Decode JSON in the consuming service.
- HTTP 4xx and 5xx responses are not transport errors. Check `code` separately.

`MockHttpClient` records every request before resolving a response. Registered URL fragments are checked in registration order. The first fragment contained in the full URL wins. When no fragment matches, it returns `is_error => true` with code `0` and an explanatory message.

### Hooks contract

```php
$hooks->addAction('admin_menu', [$controller, 'registerMenu']);
$hooks->addFilter('the_content', [$formatter, 'format'], 20, 1);

$hooks->registerRestRoute('my-plugin/v1', '/settings', [
    'methods' => 'GET',
    'callback' => [$controller, 'getSettings'],
    'permission_callback' => [$controller, 'canReadSettings'],
]);
```

For actions and filters, the third argument is priority and the fourth argument is the number of callback arguments WordPress should pass. The defaults are priority `10` and one accepted argument. REST route arguments use the same array accepted by `register_rest_route()`. Call `registerRestRoute()` from a callback registered on `rest_api_init`, just as required when calling the WordPress function directly.

`RecordingHooks` records registrations. It does not execute callbacks or run a WordPress hook lifecycle. `hasAction()` and `hasFilter()` check only the tag. `hasRestRoute()` checks only the route string, not the namespace. Use the corresponding getters when callback, priority, accepted argument count, namespace, or REST arguments matter to the assertion.

### Environment contract

`WordPressEnvironment` forwards URL, current-time, sanitization, escaping, HTML filtering, and current-screen calls to WordPress. `currentTime()` returns an integer for `timestamp` or `U`, and a string for `mysql` or another PHP date format. It exposes WordPress's default site-time behavior and does not expose the optional GMT argument from `current_time()`.

`MockEnvironment` provides controlled equivalents suitable for service tests. It returns a fixed timestamp, but formatted time values are produced by PHP's `date()` and therefore use the test process's default timezone, not the WordPress site timezone. Its sanitization, escaping, URL handling, and allowed HTML behavior are simplified PHP implementations. Do not use it to prove that WordPress sanitizes or escapes a difficult input exactly as expected. Use an integration test for that.

### Clock and logging contracts

`SystemClock::now()` returns `time()`. `FrozenClock` returns the timestamp passed to its constructor and can move forward or backward with `advance($seconds)`.

`RecordingLogger` stores PSR-3 level, message, and context. Helpers such as `hasWarning('activation_failed')` use substring matching on the message. Use `all()`, `getErrors()`, or the other level-specific getters when an exact message or context assertion is required.

## Testing adapter reference

### `InMemoryOptionStorage`

```php
use AdapterKit\Core\Testing\InMemoryOptionStorage;

$options = new InMemoryOptionStorage([
    'myplugin_settings' => ['enabled' => true],
]);

$options->update('myplugin_settings', ['enabled' => false]);
$options->has('myplugin_settings'); // true
$options->all();                    // complete in-memory store
$options->clear();                  // removes every stored key
```

Constructor values seed the store. `has()`, `all()`, and `clear()` are testing helpers and are not part of `OptionStorageInterface`.

### `InMemoryTransientStorage` and `FrozenClock`

```php
use AdapterKit\Core\Testing\InMemoryTransientStorage;
use AdapterKit\Core\Time\FrozenClock;

$clock = new FrozenClock(1700000000);
$transients = new InMemoryTransientStorage($clock);

$transients->set('token', 'abc123', 60);
$transients->get('token'); // 'abc123'

$clock->advance(60);
$transients->get('token'); // false because expiration is inclusive
```

Use an expiration of `0` for an entry that does not expire during the test.

### `MockHttpClient`

```php
use AdapterKit\Core\Testing\MockHttpClient;

$http = new MockHttpClient();
$http->addJsonResponse('/activate', ['ok' => true], 200);
$http->addErrorResponse('/timeout', 'Request timed out.');

$response = $http->post(
    'https://api.example.com/activate',
    ['body' => ['key' => 'VALID-KEY-123']]
);

$payload = json_decode($response['body'], true);
$http->wasRequestMadeTo('/activate');
$http->getLastRequest();
$http->getRequestHistory();
$http->getRequestCount();
```

`addJsonResponse()` accepts an array and JSON-encodes it into the response body. Use `addRawResponse()` when the test needs to control all four response fields directly. `addRawResponse()` does not fill in missing fields, so provide `is_error`, `error_message`, `code`, and `body`. `clear()` removes both request history and configured responses.

### `RecordingHooks`

```php
use AdapterKit\Core\Testing\RecordingHooks;

$hooks = new RecordingHooks();
$plugin->register($hooks);

$hooks->hasAction('admin_menu');
$hooks->hasFilter('the_content');
$hooks->hasRestRoute('/settings');
$hooks->getActions();
$hooks->getFilters();
$hooks->getRestRoutes();
```

The getters return the recorded callback, priority, accepted argument count, namespace, route, and REST argument data needed for detailed assertions.

### `RecordingLogger`

```php
use AdapterKit\Core\Testing\RecordingLogger;

$logger = new RecordingLogger();
$service->run($logger);

$logger->hasWarning('rate_limit_exceeded');
$logger->hasError('activation_failed');
$logger->getErrors();
$logger->count('info');
$logger->all();
$logger->clear();
```

The `has*()` helpers search for a message substring at a specific level. They do not compare the context array.

### `MockEnvironment`

```php
use AdapterKit\Core\Testing\MockEnvironment;

$environment = new MockEnvironment(
    'https://example.com',
    'https://example.com/wp-admin/',
    1700000000
);

$environment->homeUrl('pricing');
$environment->adminUrl('admin.php?page=my-plugin');
$environment->currentTime('timestamp');
$environment->currentTime('mysql');
$environment->setCurrentScreenId('settings_page_my-plugin');
$environment->getCurrentScreenId();
```

The constructor arguments are the base home URL, base admin URL, and fixed timestamp. The class trims trailing slashes from the two base URLs and joins paths with one slash. `setCurrentScreenId()` is a testing helper and is not part of `EnvironmentInterface`. Formatted time values use PHP's configured default timezone.

## PHPUnit setup

A unit-test bootstrap only needs Composer:

```php
<?php

// WordPress is not loaded.
require_once dirname(__DIR__) . '/vendor/autoload.php';
```

A minimal PHPUnit configuration can make the unit suite the default:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         defaultTestSuite="Unit"
         colors="true">

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

Run the default unit suite:

```bash
vendor/bin/phpunit --testdox
```

Integration tests are separate. They need a WordPress test bootstrap and should be run explicitly through the `Integration` suite. The unit bootstrap shown above does not load WordPress.

## Shared value types and helpers

### `PluginContext`

`PluginContext` stores immutable plugin metadata and is normally created once at bootstrap.

```php
use AdapterKit\Core\PluginContext;

$context = PluginContext::fromPluginFile(
    __FILE__,
    'my-plugin',
    '1.0.0',
    'my-plugin',
    'myplugin_'
);
```

The arguments are, in order: the main plugin file, plugin slug, plugin version, text domain, and option prefix. The option prefix is stored exactly as provided. `fromPluginFile()` calls `plugin_basename()`, `plugin_dir_path()`, and `plugin_dir_url()`, so use it only after WordPress is loaded.

```php
$context->getSlug();
$context->getVersion();
$context->getFile();
$context->getBasename();
$context->getDirPath();
$context->getDirUrl();
$context->getTextDomain();
$context->getOptionPrefix();
```

Use `PluginContext::fromValues()` in tests or another environment where WordPress path helpers are unavailable. It accepts all eight stored values directly and does not validate or normalize them.

### `Result`

`Result` provides one success-or-failure return shape for service methods.

```php
use AdapterKit\Core\Result;

$success = Result::success(['saved' => true]);
$failure = Result::failure(
    'invalid_key',
    'The license key is not valid.',
    ['field' => 'license_key']
);

$success->isSuccess();
$success->getCode();    // "success"
$success->getMessage(); // empty string
$success->getData();    // ['saved' => true]
```

Result data is always an array. Successful results always use code `success` and an empty message. Failed results use the code, message, and optional data supplied by the caller.

### `KeyBuilder`

`KeyBuilder` keeps option, transient, cache, and hook names consistent.

```php
use AdapterKit\Core\Support\KeyBuilder;

$keys = new KeyBuilder('myplugin');

$keys->option('settings');   // myplugin_settings
$keys->transient('token');   // myplugin_token
$keys->cache('license');     // myplugin_license
$keys->hook('activated');    // myplugin/activated
```

Pass the prefix without a trailing underscore or slash. `KeyBuilder` only concatenates strings. It does not sanitize the prefix or name and it does not prevent a caller from supplying separators. Use stable, already-sanitized identifiers.

## Direct-load distribution

Most WordPress plugins ship as ZIP files without a Composer runtime. WP Adapter supports that workflow.

### Build workflow

```bash
# Install development dependencies.
composer require --dev devuri/wp-adapter

# Build the direct-load bundle from the plugin root.
vendor/bin/wp-adapter-copy

# Remove vendor before packaging.
rm -rf vendor
```

Load the generated bundle from the plugin's main file:

```php
require_once __DIR__ . '/lib/wp-adapter/init.php';
```

### What the copy command does

`wp-adapter-copy`:

1. Verifies that the package source and `psr/log` source exist.
2. Rejects a source and target path overlap.
3. Copies WP Adapter and `psr/log` into a temporary sibling directory.
4. Generates a deterministic `.build-id` from the staged files.
5. Moves the existing bundle to a temporary backup.
6. Installs the complete staged bundle.
7. Restores the previous bundle if the final install fails.
8. Removes the temporary backup after a successful install.

A failed preflight or staging copy leaves the existing `lib/wp-adapter/` untouched. The fresh staged copy also prevents removed source files from surviving as stale files in a later build.

The generated `.build-id` is required runtime metadata and must ship with the plugin.

### Build conflict guard

Always load `init.php`. Do not hide it behind a `class_exists()` check.

```php
// Wrong: bypasses WP Adapter's build conflict check.
if (!class_exists(AdapterKit\Core\Result::class)) {
    require_once __DIR__ . '/lib/wp-adapter/init.php';
}

// Correct: init.php handles identical and conflicting builds.
require_once __DIR__ . '/lib/wp-adapter/init.php';
```

Runtime behavior:

- The first loaded build registers the `AdapterKit\Core\` and `Psr\Log\` autoloaders.
- Another copy with the same build ID returns without adding duplicate autoloaders.
- Another copy with a different build ID throws `RuntimeException` before registering its autoloaders.
- A missing or unreadable `.build-id` fails immediately.

The guard prevents mixed WP Adapter builds from silently serving different classes in one request. It is not namespace isolation. Different builds still cannot coexist, and the guard cannot resolve collisions with an older unguarded copy or an unrelated Composer package.

Namespace-per-plugin scoping remains the long-term coexistence solution.

## Advanced usage

### Supply a custom adapter

The contracts are the extension points. A plugin can provide its own implementation when the built-in WordPress adapter is not the right fit.

Examples include:

- a multisite-aware option store;
- a persistent-cache transient store;
- an HTTP client with plugin-specific authentication;
- a logger that forwards records to an external service.

```php
use AdapterKit\Core\Contracts\OptionStorageInterface;

final class NetworkOptionStorage implements OptionStorageInterface
{
    // Implement the contract with network option functions.
}
```

Services do not change because they already depend on `OptionStorageInterface`.

### Choose logging behavior at bootstrap

Use `NullLogger` when logs should be discarded, `WordPressDebugLogger` when logs should use PHP's `error_log()`, or another PSR-3 logger supplied by the plugin. `WordPressDebugLogger` writes only when `WP_DEBUG_LOG` is defined and truthy. Its constructor accepts a minimum PSR-3 level and defaults to `debug`.

Business logic should depend only on `Psr\Log\LoggerInterface`.

### Test time-dependent code

Inject `ClockInterface` rather than calling `time()` inside business logic.

Production uses `SystemClock`. Tests use `FrozenClock` and advance it without sleeping.

### Keep unit and integration tests separate

Unit tests should exercise business logic with testing adapters and no WordPress bootstrap.

Integration tests should verify the thin WordPress adapters against a real WordPress test environment. Examples include unchanged-option return values, scalar option type conversion, transient cache behavior, exact sanitizer output, hook registration details, and HTTP normalization.

Most plugin behavior should remain in the unit-tested service layer. Integration tests cover the WordPress-specific details that the controlled testing adapters intentionally do not emulate.

### Keep constructors focused

If a service needs many unrelated adapters, split the service by responsibility. The contracts make dependencies visible, but they do not remove the need for cohesive class design.

## Requirements

| | |
|---|---|
| PHP | 7.4, 8.0, 8.1, 8.2 |
| WordPress | No minimum enforced |
| Runtime dependency | `psr/log ^1.1` |

The package is deliberately PHP 7.4 compatible. PHP 8-only syntax such as `mixed` type declarations, constructor property promotion, and union types is not used in `src/`.

## Further reading

- [docs/testing-guide.md](docs/testing-guide.md): boundary rule, examples, PHPUnit setup, and checklist
- [docs/architecture.md](docs/architecture.md): the three-layer design and PSR adoption scope
- [docs/testing-harness.md](docs/testing-harness.md): testing adapter reference
- [docs/direct-load.md](docs/direct-load.md): complete direct-load distribution workflow
- [docs/compatibility.md](docs/compatibility.md): PHP compatibility and PSR-3 rationale
- [docs/usage.md](docs/usage.md): concise usage examples
- [examples/plugin-wiring/](examples/plugin-wiring/): runnable plugin and test example
- [examples/direct-load/](examples/direct-load/): direct-load example
- [examples/composer-usage/](examples/composer-usage/): Composer runtime example

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
