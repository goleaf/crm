---
inclusion: always
---

# Laravel Metadata Integration

## Overview
- Models can use the `HasMetadata` trait to store flexible JSON key-value metadata without schema changes.
- Metadata is stored in the polymorphic `model_meta` table with automatic type casting.
- Use `MetadataService` (registered as singleton) for complex operations; prefer the fluent trait interface for simple get/set.

## Model Setup
- Add `use App\Models\Concerns\HasMetadata;` to any model requiring metadata.
- Optionally define `public array $defaultMetaValues = ['key' => 'default'];` for exception-based data.
- Default values don't require database storage; setting metadata to default value removes the row.

## Fluent Interface
- Set: `$model->setMeta('key', 'value')` or `$model->setMeta(['key1' => 'val1', 'key2' => 'val2'])`
- Get: `$model->getMeta('key')` or `$model->getMeta(['key1', 'key2'])` or `$model->getMeta()` (all)
- Unset: `$model->unsetMeta('key')` or `$model->unsetMeta(['key1', 'key2'])`
- Check: `$model->hasMeta('key')`
- Always call `$model->save()` after setting/unsetting metadata

## Service Layer
- Use `MetadataService` for bulk operations, sync, merge, increment/decrement, toggle
- Registered as singleton: `app(MetadataService::class)` or inject via constructor
- Methods: `set()`, `get()`, `remove()`, `has()`, `all()`, `bulkSet()`, `bulkRemove()`, `sync()`, `merge()`, `increment()`, `decrement()`, `toggle()`, `getWithDefault()`

## Query Scopes
- Filter by metadata: `Model::whereMeta('key', 'value')->get()`
- Join metadata table: `Model::meta()->where('model_meta.key', 'key')->get()`

## Type Casting
- Automatic type detection and casting for: string, int, float, bool, array, object, null
- Arrays/objects stored as JSON and decoded on retrieval

## Filament Integration
- Use `KeyValue` component for metadata forms with `afterStateUpdated` to sync via service
- Display with `KeyValueEntry` in infolists
- Show metadata count in table columns with badge
- Create actions for adding/editing metadata with form modals

## Performance
- Eager load metadata: `Model::with('metas')->get()`
- Cache frequently accessed metadata
- Indexes on `metable_type`, `metable_id`, `type`, `key`
- Unique constraint on `(metable_type, metable_id, key)`

## Best Practices
- ✅ Use for flexible, optional data (feature flags, preferences, tracking metrics, integration IDs)
- ✅ Define default values for common states
- ✅ Eager load when accessing multiple models
- ✅ Use service layer for complex operations
- ✅ Validate values before setting
- ❌ Don't store critical business data only in metadata
- ❌ Don't use for data needing complex queries
- ❌ Don't forget to save after setting
- ❌ Don't skip eager loading in loops
- ❌ Don't store large binary data

## Documentation
- Full guide: `docs/laravel-metadata-integration.md`
- Package: `kodeine/laravel-meta` (v2.2.5)
- Migration: `database/migrations/2025_01_12_000000_create_model_meta_table.php`
- Trait: `app/Models/Concerns/HasMetadata.php`
- Service: `app/Services/Metadata/MetadataService.php`
- Tests: `tests/Unit/Services/Metadata/MetadataServiceTest.php`, `tests/Feature/Metadata/HasMetadataTraitTest.php`
