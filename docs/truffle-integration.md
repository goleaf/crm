# Truffle In-Memory Models

## Overview
`waad/truffle` gives us SQLite-backed, in-memory Eloquent models. Use this when you need static reference data or code-defined fixtures that should still be queryable via Eloquent without a migration.

## Base Model
- Extend `App\Models\InMemoryModel` (wraps the Truffle trait and our shared base model).
- Defaults: `$timestamps = false`, `$insertChunkRecords = 500`.
- Define `$records` for seed data and optionally `$schema` with `Waad\Truffle\Enums\DataType` for explicit column types. Schema is inferred from the first record when omitted.
- Use casts for arrays/JSON columns so decoded values stay typed (e.g., `$casts = ['tags' => 'array'];`).
- `rebuildInMemoryData()` clears the connection and reruns the migration + seeding for the model (useful in tests when `$records` change).
- Real example: `App\Models\InMemory\SupportTierPreset` seeds support tiers for UI defaults and quoting helpers.

### Example
```php
use App\Models\InMemoryModel;
use Waad\Truffle\Enums\DataType;

final class StaticProduct extends InMemoryModel
{
    protected $table = 'static_products';

    protected array $records = [
        ['id' => 1, 'name' => 'Laptop', 'price' => 999.99, 'tags' => ['hardware']],
        ['id' => 2, 'name' => 'Mug', 'price' => 12.50, 'tags' => ['kitchen']],
    ];

    protected array $schema = [
        'id' => DataType::Id,
        'name' => DataType::String,
        'price' => DataType::Decimal,
        'tags' => DataType::Json,
    ];

    protected array $casts = [
        'tags' => 'array',
    ];
}

// Usage
$items = StaticProduct::query()->where('price', '>', 100)->orderBy('price')->get();
$avg = StaticProduct::avg('price');
```

## When to Use
- Small, code-defined reference sets (labels, lookup tables, feature metadata).
- Test fixtures that should be queryable without hitting the primary database connection.
- Temporary data for read-heavy dashboards where persistence is not required.

## Conventions
- Keep datasets small; this is not a replacement for persisted tables.
- Prefer explicit `$schema` definitions for clarity and type safety when IDs are strings/UUIDs.
- Use `StaticProduct::rebuildInMemoryData()` in tests if you mutate `$records` between assertions to reset the in-memory connection.
- Continue using `App\Models\Model` for persisted data; only Truffle-backed models should extend `App\Models\InMemoryModel`.
