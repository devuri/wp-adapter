# WP Adapter

WordPress adapter contracts and in-memory testing doubles for clean, testable plugin development.

```bash
composer require --dev devuri/wp-adapter
```

**PHP 7.4+ · MIT · No WordPress at runtime**

---

## The problem this solves

WordPress plugins commonly call `get_option()`, `add_action()`, and `wp_remote_post()` directly inside business logic. That makes the logic impossible to unit test without bootstrapping WordPress, and it makes the plugin hard to reason about.

WP Adapter gives us a thin set of contracts for common WordPress APIs and matching in-memory implementations for tests. Our plugin code depends only on the contracts. WordPress stays at the edge.

```php
// Business logic depends on the contract, not WordPress
final class LicenseService
{
    private OptionStorageInterface $options;
    private HttpClientInterface    $http;
    private LoggerInterface        $logger;

    public function __construct(
        OptionStorageInterface $options,
        HttpClientInterface    $http,
        LoggerInterface        $logger
    ) {
        $this->options = $options;
        $this->http    = $http;
        $this->logger  = $logger;
    }

    public function activate(string $key): Result
    {
        // Pure logic. No WordPress functions. Fully unit-testable.
    }
}
```

In production we pass the WordPress adapters. In tests we pass the in-memory fakes. No mocks. No bootstrapping WordPress.

---

## Our plugin must follow the boundary rule

**This package cannot help us if our business logic calls WordPress functions directly.** The adapters are only useful when our plugin is structured so that service classes receive their dependencies through the constructor as contracts.

The rule: WordPress function calls (`get_option`, `add_action`, `wp_remote_post`, etc.) belong only in the thin adapter classes that implement the contracts. Every other class must call only the interface, never WordPress.

If we call `get_option()` inside a service, that service requires WordPress to exist and cannot be unit tested. The testing adapters in this package will have no effect.

See **[docs/testing-guide.md](docs/testing-guide.md)** for the full structure, a wrong-vs-right example, PHPUnit setup, and a checklist.

---

## Installation

Install as a dev dependency during development:

```bash
composer require --dev devuri/wp-adapter
```

Copy the source into our plugin at build time:

```bash
vendor/bin/wp-adapter-copy
```

This copies `src/` and `psr/log` into `lib/wp-adapter/` inside our plugin. Load it from our plugin's main file:

```php
require_once __DIR__ . '/lib/wp-adapter/init.php';
```

Strip `vendor/` before distributing. `lib/` ships with the plugin. See [Direct-load distribution](#direct-load-distribution) for the full workflow.

---

## Wiring production adapters

```php
use AdapterKit\Core\PluginContext;
use AdapterKit\Core\Hooks\WordPressHooks;
use AdapterKit\Core\Storage\WordPressOptionStorage;
use AdapterKit\Core\Storage\WordPressTransientStorage;
use AdapterKit\Core\Http\WordPressHttpClient;
use AdapterKit\Core\Logging\NullLogger;

$context = PluginContext::fromPluginFile(
    __FILE__, 'my-plugin', '1.0.0', 'my-plugin', 'myplugin_'
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

---

## Unit testing without WordPress

Swap in the in-memory testing adapters. No WordPress bootstrap required.

```php
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
            $this->options, $this->http, $this->logger, 'myplugin_license'
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

    public function test_activate_returns_failure_and_logs_warning_on_http_error(): void
    {
        $this->http->addErrorResponse('/activate', 'Connection refused.');

        $result = $this->service->activate('ANY-KEY');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('activation_failed', $result->getCode());
        $this->assertTrue($this->logger->hasWarning('activation_failed'));
    }
}
```

### PHPUnit bootstrap (`tests/bootstrap.php`)

```php
<?php
// WordPress is NOT loaded.
require_once dirname(__DIR__) . '/vendor/autoload.php';
```

One line. Composer's autoloader includes `devuri/wp-adapter` and `psr/log`. All contracts and testing adapters are available. No WordPress, no `WP_TESTS_DIR`.

### PHPUnit config (`phpunit.xml.dist`)

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

`defaultTestSuite="Unit"` ensures `vendor/bin/phpunit` never loads the integration suite. Integration tests (those that require WordPress) must be marked `@group integration` and run explicitly:

```bash
# Unit only (default — no WordPress needed)
vendor/bin/phpunit --testdox

# Integration only (requires WP_TESTS_DIR)
WP_TESTS_DIR=/path/to/wordpress-tests-lib vendor/bin/phpunit --testsuite Integration
```

See `examples/plugin-wiring/` for a complete, runnable example with service class, plugin class, and tests.

---

## Testing adapters

All testing adapters live in `AdapterKit\Core\Testing\` and are public, versioned API.

### `InMemoryOptionStorage`

```php
$options = new InMemoryOptionStorage(['myplugin_settings' => ['enabled' => true]]);
$options->update('myplugin_settings', ['enabled' => false]);
$options->has('myplugin_settings');  // true
$options->all();                      // full store contents
$options->clear();
```

### `InMemoryTransientStorage` + `FrozenClock`

```php
$clock      = new FrozenClock(1700000000);
$transients = new InMemoryTransientStorage($clock);
$transients->set('token', 'abc123', 60);
$transients->get('token');   // 'abc123'
$clock->advance(61);
$transients->get('token');   // false — expired
```

### `MockHttpClient`

```php
$http = new MockHttpClient();
$http->addJsonResponse('/activate', ['ok' => true], 200);
$http->addErrorResponse('/timeout', 'Request timed out.');

$http->post('https://api.example.com/activate', []);

$http->wasRequestMadeTo('/activate');  // true
$http->getLastRequest();               // ['method' => 'POST', 'url' => ..., ...]
$http->getRequestCount();              // 1
```

### `RecordingHooks`

```php
$hooks = new RecordingHooks();
$plugin->register($hooks);

$hooks->hasAction('admin_menu');                     // bool
$hooks->hasFilter('the_content');                    // bool
$hooks->hasRestRoute('/my-plugin/v1/settings');      // bool
$hooks->getActions();                                // array of all recorded actions
```

### `RecordingLogger`

```php
$logger = new RecordingLogger();
$service->run($logger);

$logger->hasWarning('rate_limit_exceeded');  // bool
$logger->hasError('activation_failed');      // bool
$logger->getErrors();                        // array
$logger->count('info');                      // int
$logger->clear();
```

### `MockEnvironment`

```php
$env = new MockEnvironment(
    'https://example.com',
    'https://example.com/wp-admin/',
    1700000000
);

$env->homeUrl('pricing');
$env->adminUrl('admin.php?page=my-plugin');
$env->setCurrentScreenId('settings_page_my-plugin');
$env->sanitizeTextField(' hello world ');  // 'hello world'
```

---

## Contracts

Six interfaces in `AdapterKit\Core\Contracts\`. Our plugin code depends only on these.

| Contract | Production adapter | Testing adapter |
|---|---|---|
| `HooksInterface` | `WordPressHooks` | `RecordingHooks` |
| `OptionStorageInterface` | `WordPressOptionStorage` | `InMemoryOptionStorage` |
| `TransientStorageInterface` | `WordPressTransientStorage` | `InMemoryTransientStorage` |
| `EnvironmentInterface` | `WordPressEnvironment` | `MockEnvironment` |
| `HttpClientInterface` | `WordPressHttpClient` | `MockHttpClient` |
| `ClockInterface` | `SystemClock` | `FrozenClock` |

`LoggerInterface` is `Psr\Log\LoggerInterface`. `NullLogger` and `WordPressDebugLogger` are the production implementations.

---

## Shared value types

**`PluginContext`** — immutable plugin metadata populated once at bootstrap.

```php
$ctx = PluginContext::fromPluginFile(__FILE__, 'my-plugin', '1.0.0', 'my-plugin', 'myplugin_');

$ctx->getSlug();          // 'my-plugin'
$ctx->getVersion();       // '1.0.0'
$ctx->getDirPath();       // absolute path with trailing slash
$ctx->getDirUrl();        // URL with trailing slash
$ctx->getOptionPrefix();  // 'myplugin_'
```

**`Result`** — shared return type for service methods.

```php
$result = Result::success(['saved' => true]);
$result = Result::failure('invalid_key', 'The license key is not valid.');

$result->isSuccess();   // bool
$result->getCode();     // string
$result->getMessage();  // string
$result->getData();     // array
```

**`KeyBuilder`** — prevents option/transient/hook naming drift.

```php
$keys = new KeyBuilder('myplugin_');
$keys->option('settings');    // myplugin_settings
$keys->transient('token');    // myplugin_token
$keys->hook('activated');     // myplugin_/activated
```

---

## Direct-load distribution

WordPress plugins are distributed as ZIP files without a Composer runtime. WP Adapter supports this out of the box.

**Development workflow:**

```bash
# 1. Install as a dev dependency
composer require --dev devuri/wp-adapter

# 2. Copy into lib/ (run this at build time, not at runtime)
vendor/bin/wp-adapter-copy

# 3. Load in our plugin's main file
# require_once __DIR__ . '/lib/wp-adapter/init.php';

# 4. Strip vendor/ before packaging. lib/ ships with the plugin.
```

`wp-adapter-copy` copies `src/` and a PHP 7.4-safe copy of `psr/log` into `lib/wp-adapter/`. The `init.php` entry point registers two PSR-4 autoloaders — one for `AdapterKit\Core\` and one for `Psr\Log\` — so no Composer is needed on the end user's server.

**Do not use a `class_exists` guard:**

```php
// Wrong — silently accepts the first loaded version if multiple plugins use this package
if (! class_exists(AdapterKit\Core\Result::class)) {
    require_once __DIR__ . '/lib/wp-adapter/init.php';
}

// Correct — load unconditionally
require_once __DIR__ . '/lib/wp-adapter/init.php';
```

Namespace-per-plugin scoping is deferred to a future build step.

---

## Requirements

| | |
|---|---|
| PHP | 7.4, 8.0, 8.1, 8.2 |
| WordPress | No minimum enforced |
| Dependencies | `psr/log ^1.1` (runtime) |

The package is deliberately PHP 7.4 compatible throughout. `mixed` type hints, constructor property promotion, union types, and all other PHP 8.0+ syntax are forbidden in `src/`.

---

## Further reading

- [docs/testing-guide.md](docs/testing-guide.md) — boundary rule, wrong-vs-right examples, PHPUnit setup, checklist
- [docs/architecture.md](docs/architecture.md) — three-layer design, contract table, PSR adoption scope
- [docs/direct-load.md](docs/direct-load.md) — full direct-load distribution workflow
- [docs/compatibility.md](docs/compatibility.md) — PHP version matrix, forbidden syntax, PSR-3 pin rationale
- [examples/plugin-wiring/](examples/plugin-wiring/) — complete example with service, plugin class, and unit tests

---

## License

MIT — see [LICENSE](LICENSE).

Maintained by [Premium7 / Devuri](https://github.com/devuri).
