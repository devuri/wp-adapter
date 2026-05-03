# Testing Guide

The point of WP Adapter is to make plugin unit tests run without WordPress. The
package provides the tools. **Your plugin must be structured to use them.** If
your business logic calls `get_option()`, `wp_remote_post()`, or any other
WordPress function directly, the adapters cannot help you and the unit tests
will fail without a WordPress bootstrap.

This guide explains the required structure and how to set it up.

---

## The rule: WordPress stays at the edge

WordPress function calls belong only in classes that implement the adapter
contracts. Every other class in your plugin must receive its dependencies
through constructor injection and call only the contract interfaces.

The test for whether you've followed this rule is simple: can you instantiate
your service class in a plain PHP file with no WordPress loaded?

```bash
php -r "
require 'vendor/autoload.php';
\$s = new MyPlugin\LicenseService(
    new AdapterKit\Core\Testing\InMemoryOptionStorage([]),
    new AdapterKit\Core\Testing\MockHttpClient(),
    new AdapterKit\Core\Testing\RecordingLogger(),
    'myplugin_settings'
);
echo get_class(\$s) . PHP_EOL;
"
```

If that exits cleanly, the boundary is intact. If it fatals with a call to
`get_option` or `add_action`, the boundary is broken.

---

## Wrong vs right

**Wrong — untestable.** WordPress functions are called directly inside business logic.

```php
final class LicenseService
{
    public function activate(string $key): bool
    {
        $response = wp_remote_post('https://api.example.com/activate', [
            'body' => ['key' => $key],
        ]);

        if (is_wp_error($response)) {
            update_option('myplugin_license', ['active' => false]);
            return false;
        }

        update_option('myplugin_license', ['active' => true, 'key' => $key]);
        return true;
    }
}
```

This class cannot be unit tested. Every test must bootstrap WordPress. You
cannot control what `wp_remote_post` returns. You cannot inspect what
`update_option` stored. WordPress is baked into the logic.

---

**Right — testable.** The same behaviour, but WordPress-free.

```php
use AdapterKit\Core\Contracts\HttpClientInterface;
use AdapterKit\Core\Contracts\OptionStorageInterface;
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
            return Result::failure('activation_failed', $response['error_message']);
        }

        $this->options->update($this->optionKey, [
            'active' => true,
            'key'    => $key,
        ]);

        return Result::success(['active' => true]);
    }

    public function isActive(): bool
    {
        $stored = $this->options->get($this->optionKey, []);
        return is_array($stored) && !empty($stored['active']);
    }
}
```

The logic is identical. The difference is that every collaborator enters
through the constructor as a contract. In production you pass WordPress
adapters. In tests you pass the in-memory fakes.

---

## Anatomy of a testable plugin

```
plugin.php               <- WordPress plugin header; wires adapters; calls register()
src/
    Plugin.php           <- registers hooks via HooksInterface; builds services
    LicenseService.php   <- pure business logic; depends only on contracts
    SettingsService.php  <- same
tests/
    bootstrap.php        <- require vendor/autoload.php; NO WordPress loaded
    Unit/
        LicenseServiceTest.php
        SettingsServiceTest.php
    Integration/         <- these need WordPress; marked @group integration
        ...
phpunit.xml.dist
```

**`plugin.php`** — the only file that touches WordPress APIs directly at load time:

```php
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

**`Plugin.php`** — registers hooks, builds services from the injected adapters. Never calls `get_option()` or `wp_remote_post()` directly.

**Service classes** — pure logic. No WordPress functions. Accept contracts through the constructor. Return `Result` objects. Fully unit-testable.

---

## Setting up PHPUnit

### `composer.json` (dev dependencies)

```json
{
    "require-dev": {
        "devuri/wp-adapter": "^0.1",
        "phpunit/phpunit": "^9.6"
    },
    "autoload": {
        "psr-4": { "MyPlugin\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "MyPlugin\\Tests\\": "tests/" }
    }
}
```

### `phpunit.xml.dist`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
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

`defaultTestSuite="Unit"` means `vendor/bin/phpunit` (no arguments) runs only
unit tests. Integration tests require `--testsuite Integration` and a WordPress
test bootstrap. This prevents the integration suite from blocking CI when
WordPress is not available.

### `tests/bootstrap.php`

```php
<?php

// Unit test bootstrap. WordPress is NOT loaded.
require_once dirname(__DIR__) . '/vendor/autoload.php';
```

That one line is enough. Composer's autoloader includes `devuri/wp-adapter`
(and `psr/log`) so all contracts and testing adapters are available. No
WordPress, no `WP_TESTS_DIR`, no `functions.php`.

---

## Writing unit tests

```php
use PHPUnit\Framework\TestCase;
use AdapterKit\Core\Testing\InMemoryOptionStorage;
use AdapterKit\Core\Testing\MockHttpClient;
use AdapterKit\Core\Testing\RecordingLogger;
use MyPlugin\LicenseService;

final class LicenseServiceTest extends TestCase
{
    private InMemoryOptionStorage $options;
    private MockHttpClient        $http;
    private RecordingLogger       $logger;
    private LicenseService        $service;

    protected function setUp(): void
    {
        $this->options = new InMemoryOptionStorage(['myplugin_settings' => []]);
        $this->http    = new MockHttpClient();
        $this->logger  = new RecordingLogger();
        $this->service = new LicenseService(
            $this->options,
            $this->http,
            $this->logger,
            'myplugin_settings'
        );
    }

    public function test_activate_stores_key_when_api_succeeds(): void
    {
        $this->http->addJsonResponse('/activate', ['ok' => true], 200);

        $result = $this->service->activate('VALID-KEY-123');

        $this->assertTrue($result->isSuccess());
        $stored = $this->options->get('myplugin_settings');
        $this->assertTrue($stored['active']);
        $this->assertSame('VALID-KEY-123', $stored['key']);
    }

    public function test_activate_returns_failure_when_api_errors(): void
    {
        $this->http->addErrorResponse('/activate', 'Connection refused.');

        $result = $this->service->activate('ANY-KEY');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('activation_failed', $result->getCode());
        $this->assertTrue($this->logger->hasWarning('activation_failed'));
    }

    public function test_is_active_is_false_before_activation(): void
    {
        $this->assertFalse($this->service->isActive());
    }

    public function test_activate_request_is_sent_to_correct_endpoint(): void
    {
        $this->http->addJsonResponse('/activate', ['ok' => true], 200);
        $this->service->activate('KEY');
        $this->assertTrue($this->http->wasRequestMadeTo('/activate'));
    }
}
```

No WordPress. No mocks. Runs in milliseconds. Every assertion is deterministic.

---

## Separating unit and integration tests

Integration tests call real WordPress functions and extend `WP_UnitTestCase`.
Mark each integration test class with `@group integration` so they are clearly
identified and can be excluded from the default run.

```php
/**
 * @group integration
 */
final class LicenseActivationIntegrationTest extends WP_UnitTestCase
{
    public function test_stored_option_is_readable_by_wordpress(): void
    {
        // Tests that WordPressOptionStorage writes what get_option() can read.
    }
}
```

Integration tests are run explicitly with a WordPress environment:

```bash
WP_TESTS_DIR=/path/to/wordpress-tests-lib vendor/bin/phpunit --testsuite Integration
```

The ratio to aim for: the vast majority of tests should be unit tests. If you
find yourself writing more integration tests than unit tests, that is a sign
that business logic has leaked into the adapter layer.

---

## Common mistakes

**Calling WordPress functions inside a service**

```php
// Breaks the boundary — LicenseService now requires WordPress to exist
public function activate(string $key): Result
{
    $settings = get_option('myplugin_settings', []);  // ← wrong
    ...
}
```

Move option reads into a method that receives an `OptionStorageInterface`.

---

**Extending a WordPress class in a service**

```php
// Wrong — your service now inherits WordPress state
final class LicenseService extends WP_REST_Controller
```

Use plain PHP classes. Accept `HooksInterface` and `EnvironmentInterface`
through the constructor instead.

---

**Using static methods or global state**

```php
// Wrong — cannot be overridden in tests
$settings = MyPlugin::getSettings();
```

Pass settings through the constructor via `OptionStorageInterface`.

---

**Injecting the concrete adapter instead of the contract**

```php
// Wrong — ties service to WordPress even in tests
public function __construct(WordPressOptionStorage $options)
```

Always type-hint against the interface:

```php
public function __construct(OptionStorageInterface $options)
```

---

## Checklist

Before shipping a feature, verify:

- [ ] Service classes have no `use` statements importing WordPress classes
- [ ] No `get_option`, `update_option`, `wp_remote_*`, `add_action`, `add_filter` calls outside adapter implementations
- [ ] `vendor/bin/phpunit` (default suite) passes without `WP_TESTS_DIR`
- [ ] New behaviour is covered by a unit test using the testing adapters
- [ ] Integration tests are marked `@group integration`
