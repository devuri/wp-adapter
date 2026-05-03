# Changelog

## [Unreleased] - v0.1.0

### Added
- `PluginContext` - immutable plugin metadata with `fromPluginFile()` and `fromValues()` factories.
- `Result` - shared success/failure return type.
- Contracts: `HooksInterface`, `OptionStorageInterface`, `TransientStorageInterface`, `EnvironmentInterface`, `HttpClientInterface`, `ClockInterface`.
- WordPress adapters: `WordPressHooks`, `WordPressOptionStorage`, `WordPressTransientStorage`, `WordPressEnvironment`, `WordPressHttpClient`.
- `KeyBuilder` - prevents naming drift for options, transients, hooks, and cache entries.
- `SystemClock` and `FrozenClock` - production and testing time implementations.
- `NullLogger` and `WordPressDebugLogger` - PSR-3 compliant loggers.
- Testing adapters (public API): `InMemoryOptionStorage`, `InMemoryTransientStorage`, `MockEnvironment`, `MockHttpClient`, `RecordingHooks`, `RecordingLogger`.
- `init.php` direct-load entry point for plugin distribution without Composer.
- `bin/wp-adapter-copy` - Composer binary for copying the package into `lib/wp-adapter/`.
- `bin/check.sh` - internal dev tool running syntax, unit tests, PHPStan, and PSR-12.
- Full unit and integration test suites.
