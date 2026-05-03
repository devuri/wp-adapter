# Architecture

## Three equal layers

WP Adapter has three layers. All three are versioned and maintained to the
same standard. The testing layer is public API, not an internal test suite.

```
WP Plugin
│
├── Business Logic ──────────────────────> Contracts (interfaces)
│                                                │
│                                    ┌───────────┴───────────┐
│                                    │                       │
│                            WordPress Adapters      Testing Adapters
│                            (production)            (unit tests)
│                                    │
│                            WordPress APIs
```

| Layer | Namespace | Role |
|---|---|---|
| Contracts | `AdapterKit\Core\Contracts\` | Define the boundary. Plugin code depends only on these. |
| WordPress adapters | `AdapterKit\Core\` (non-Testing) | Call WordPress APIs. Integration-tested only. |
| Testing adapters | `AdapterKit\Core\Testing\` | Deterministic in-memory fakes. Unit-tested. |

## Boundary rule

WordPress function calls (`get_option`, `add_action`, `wp_remote_post`, etc.)
belong only in the WordPress adapter classes. Business logic in plugin code must
never call WordPress functions directly — it receives adapter instances through
constructor injection and calls only the contract interfaces.

The one approved exception is `PluginContext::fromPluginFile()`, which calls
`plugin_basename()`, `plugin_dir_path()`, and `plugin_dir_url()` as a
bootstrap-edge helper. It is integration-tested; use `PluginContext::fromValues()`
in unit tests.

## Contracts

Six package-owned interfaces in `src/Contracts/`:

| Interface | Production adapter | Testing adapter |
|---|---|---|
| `HooksInterface` | `WordPressHooks` | `RecordingHooks` |
| `OptionStorageInterface` | `WordPressOptionStorage` | `InMemoryOptionStorage` |
| `TransientStorageInterface` | `WordPressTransientStorage` | `InMemoryTransientStorage` |
| `EnvironmentInterface` | `WordPressEnvironment` | `MockEnvironment` |
| `HttpClientInterface` | `WordPressHttpClient` | `MockHttpClient` |
| `ClockInterface` | `SystemClock` | `FrozenClock` |

`LoggerInterface` is `Psr\Log\LoggerInterface` — no package-owned file.

## Shared value types

- `PluginContext` — immutable plugin metadata, passed to the plugin constructor.
- `Result` — shared success/failure return type for service methods.
- `KeyBuilder` — prevents option/transient/hook naming drift across a plugin.

## PSR adoption

PSR adoption is an internal quality decision for this package. Plugins that
consume WP Adapter do not need to adopt any PSR standard.

| Standard | Scope |
|---|---|
| PSR-3 (logging) | `psr/log ^1.1` is the only runtime dependency. Pinned to v1 for PHP 7.4 safety (see `docs/compatibility.md`). |
| PSR-4 (autoloading) | `AdapterKit\Core\` maps to `src/`. |
| PSR-12 (code style) | Enforced on `src/` only via phpcs. |
| PSR-16, PSR-18, PSR-7 | Deferred. PSR-16 adds method bloat; PSR-18 requires PSR-7 which is too heavy for this scope. |

## Design smell limit

If a class needs more than 3–4 mocks to unit test, refactor the class. The
adapter pattern should make most classes testable with one or two fakes at most.
