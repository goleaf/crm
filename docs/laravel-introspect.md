# Laravel Introspect Integration

`mateffy/laravel-introspect` is available for codebase-aware tooling (LLM context, developer utilities, extension scaffolding). The package is bound in the container so `Introspect::...` and injected services automatically scan the core app plus module sources.

## Configuration (`config/introspect.php`)
- `directories`: defaults to `app` and any `app-modules/*/src` folders (update if new module paths are added).
- `namespaces`: limits class/model exports (defaults: `App\`, `Relaticle\`).
- `cache.key` / `cache.ttl`: snapshot cache key and seconds-to-live (`INTROSPECT_CACHE_TTL`, 0 disables caching).
- `export.path`: default JSON export location (`storage/app/introspection/snapshot.json`).

## Service Usage
- Inject `App\Services\AI\CodebaseIntrospectionService`.
- `snapshot(bool $forceRefresh = false)`: returns arrays for `views`, `routes`, `classes`, `models`, plus `generated_at` and `directories`.
- `views()`, `routes()`, `classes()`, `models()`: query-specific exports.
- `flushCache()`: clear the configured cache entry.

Example:

```php
$snapshot = app(\App\Services\AI\CodebaseIntrospectionService::class)->snapshot();
$models = $snapshot['models']; // classpath, description, schema, properties
```

## CLI Export
- Command: `php artisan introspect:export [--fresh] [--path=...]`
- Writes a JSON snapshot (respecting cache unless `--fresh`) to the configured path; useful for feeding LLM tools or external diagnostics without hitting the filesystem repeatedly.
