# Testing Harness

`src/Testing/` is public API. Plugins consume it the same way they consume production adapters.

## InMemoryOptionStorage

```php
use AdapterKit\Core\Testing\InMemoryOptionStorage;

$options = new InMemoryOptionStorage(['pp7_settings' => ['enabled' => true]]);
$options->update('pp7_settings', ['enabled' => false]);
$options->has('pp7_settings');  // true
$options->all();                // full contents
$options->clear();              // reset
```

## InMemoryTransientStorage + FrozenClock

```php
use AdapterKit\Core\Testing\InMemoryTransientStorage;
use AdapterKit\Core\Time\FrozenClock;

$clock      = new FrozenClock(1700000000);
$transients = new InMemoryTransientStorage($clock);
$transients->set('key', 'value', 60);
$transients->get('key');   // 'value'
$clock->advance(61);
$transients->get('key');   // false - expired
```

## MockHttpClient

```php
use AdapterKit\Core\Testing\MockHttpClient;

$http = new MockHttpClient();
$http->addJsonResponse('/activate', ['ok' => true], 200);
$http->addErrorResponse('/timeout', 'Request timed out.');

$response = $http->post('https://api.example.com/activate', []);
$http->wasRequestMadeTo('/activate');  // true
$http->getLastRequest();               // full request array
$http->getRequestCount();              // int
```

## RecordingHooks

```php
use AdapterKit\Core\Testing\RecordingHooks;

$hooks = new RecordingHooks();
$plugin->register($hooks);

$hooks->hasAction('admin_menu');       // bool
$hooks->hasFilter('the_content');      // bool
$hooks->hasRestRoute('/settings');     // bool
$hooks->getActions();                  // all recorded actions
```

## RecordingLogger

```php
use AdapterKit\Core\Testing\RecordingLogger;

$logger = new RecordingLogger();
$service->run($logger);

$logger->hasWarning('rate_limit_exceeded');  // bool
$logger->getErrors();                        // array
$logger->count('info');                      // int
$logger->clear();
```

## MockEnvironment

```php
use AdapterKit\Core\Testing\MockEnvironment;

$env = new MockEnvironment('https://example.com', 'https://example.com/wp-admin/', 1700000000);
$env->homeUrl('pricing');
$env->adminUrl('admin.php?page=my-plugin');
$env->setCurrentScreenId('settings_page_my-plugin');
$env->sanitizeTextField(' hello ');  // 'hello'
```
