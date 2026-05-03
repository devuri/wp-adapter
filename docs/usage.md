# Usage

## Installation

### As a dev dependency (recommended)

```bash
composer require --dev devuri/wp-adapter
```

Copy into `lib/` at build time:

```bash
vendor/bin/wp-adapter-copy
```

### Direct load (no Composer at runtime)

```php
require_once __DIR__ . '/lib/wp-adapter/init.php';
```

## Wiring production adapters

```php
use AdapterKit\Core\PluginContext;
use AdapterKit\Core\Hooks\WordPressHooks;
use AdapterKit\Core\Storage\WordPressOptionStorage;
use AdapterKit\Core\Storage\WordPressTransientStorage;
use AdapterKit\Core\Http\WordPressHttpClient;
use AdapterKit\Core\Logging\NullLogger;

$context = PluginContext::fromPluginFile(
    __FILE__, 'my-plugin', '1.0.0', 'my-plugin', 'pp7_my_plugin'
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

## Using KeyBuilder

```php
use AdapterKit\Core\Support\KeyBuilder;

$keys = new KeyBuilder('pp7_my_plugin');
$keys->option('settings');    // pp7_my_plugin_settings
$keys->transient('cache_1');  // pp7_my_plugin_cache_1
$keys->hook('saved');         // pp7_my_plugin/saved
```

## Using Result

```php
use AdapterKit\Core\Result;

$result = Result::success(['saved' => true]);
$result = Result::failure('invalid_input', 'The field is required.');

$result->isSuccess();  // bool
$result->getCode();    // string
$result->getMessage(); // string
$result->getData();    // array
```
