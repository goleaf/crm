# Laravel Metadata Integration

## Overview

The metadata system provides flexible JSON-based key-value storage for Eloquent models without requiring schema changes. Built on top of `kodeine/laravel-meta`, it follows our service container patterns and integrates seamlessly with Filament v4.3+.

## Package Information

- **Package**: `kodeine/laravel-meta` (v2.2.5)
- **Purpose**: Fluent metadata management for Eloquent models
- **Storage**: Polymorphic `model_meta` table with automatic type casting
- **Integration**: Custom trait, service layer, and Filament components

## Architecture

### Components

1. **ModelMeta Model** (`app/Models/ModelMeta.php`)
   - Stores metadata entries with automatic type casting
   - Supports polymorphic relationships to any model
   - Tracks deletion markers for fluent unset operations

2. **HasMetadata Trait** (`app/Models/Concerns/HasMetadata.php`)
   - Adds metadata capabilities to any model
   - Provides fluent interface for get/set/unset operations
   - Supports default values and query scopes

3. **MetadataService** (`app/Services/Metadata/MetadataService.php`)
   - Centralized service for metadata operations
   - Registered as singleton in AppServiceProvider
   - Provides bulk operations, sync, merge, increment/decrement

4. **Migration** (`database/migrations/2025_01_12_000000_create_model_meta_table.php`)
   - Creates `model_meta` table with polymorphic structure
   - Unique constraint on (metable_type, metable_id, key)
   - Indexed for performance

## Installation

The package is already installed via Composer:

```bash
composer require kodeine/laravel-meta
```

Run the migration:

```bash
php artisan migrate
```

## Model Setup

### Basic Usage

Add the `HasMetadata` trait to any model:

```php
use App\Models\Concerns\HasMetadata;
use App\Models\Model;

final class Company extends Model
{
    use HasMetadata;
}
```

### With Default Values

Define default metadata values that don't require database storage:

```php
final class Company extends Model
{
    use HasMetadata;
    
    /**
     * Default metadata values.
     *
     * @var array<string, mixed>
     */
    public array $defaultMetaValues = [
        'is_verified' => false,
        'notification_enabled' => true,
        'theme' => 'light',
    ];
}
```

**Behavior:**
- If metadata doesn't exist, default value is returned
- Setting metadata to default value removes the database row
- Useful for exception-based data (e.g., "user is sick" vs default "user is working")

## Fluent Interface

### Setting Metadata

```php
// Single value
$company->setMeta('industry_notes', 'Tech startup focused on AI');
$company->save();

// Multiple values
$company->setMeta([
    'industry_notes' => 'Tech startup',
    'employee_growth_rate' => 15.5,
    'is_partner' => true,
]);
$company->save();

// Fluent chaining
$company->name = 'Acme Corp';
$company->setMeta('industry_notes', 'Manufacturing');
$company->save();
```

### Getting Metadata

```php
// Single value
$notes = $company->getMeta('industry_notes');

// Multiple values (returns Collection)
$data = $company->getMeta(['industry_notes', 'employee_growth_rate']);

// All metadata (returns Collection)
$allMeta = $company->getMeta();

// With raw ModelMeta objects
$raw = $company->getMeta('industry_notes', raw: true);
```

### Unsetting Metadata

```php
// Single value
$company->unsetMeta('industry_notes');
$company->save();

// Multiple values
$company->unsetMeta(['industry_notes', 'employee_growth_rate']);
$company->save();

// Fluent unset
unset($company->industry_notes);
$company->save();
```

### Checking Existence

```php
if ($company->hasMeta('industry_notes')) {
    // Metadata exists
}
```

## Service Layer

### Registration

The `MetadataService` is registered as a singleton in `AppServiceProvider`:

```php
use App\Services\Metadata\MetadataService;

public function register(): void
{
    $this->app->singleton(MetadataService::class);
}
```

### Usage

```php
use App\Services\Metadata\MetadataService;

$metadataService = app(MetadataService::class);

// Set metadata
$metadataService->set($company, 'industry_notes', 'Tech startup');

// Get metadata
$notes = $metadataService->get($company, 'industry_notes');

// Check existence
if ($metadataService->has($company, 'industry_notes')) {
    // ...
}

// Remove metadata
$metadataService->remove($company, 'industry_notes');

// Get all metadata
$allMeta = $metadataService->all($company);
```

### Bulk Operations

```php
// Bulk set
$metadataService->bulkSet($company, [
    'industry_notes' => 'Tech startup',
    'employee_growth_rate' => 15.5,
    'is_partner' => true,
]);

// Bulk remove
$metadataService->bulkRemove($company, [
    'industry_notes',
    'employee_growth_rate',
]);

// Sync (replace all metadata)
$metadataService->sync($company, [
    'new_key' => 'new_value',
]);

// Merge (combine with existing)
$metadataService->merge($company, [
    'additional_key' => 'additional_value',
]);
```

### Numeric Operations

```php
// Increment
$metadataService->increment($company, 'view_count');
$metadataService->increment($company, 'revenue', 1000.50);

// Decrement
$metadataService->decrement($company, 'stock_count');
$metadataService->decrement($company, 'balance', 50.25);

// Toggle boolean
$metadataService->toggle($company, 'is_featured');
```

### Default Values

```php
// Get with default if not exists
$theme = $metadataService->getWithDefault($company, 'theme', 'light');
```

## Query Scopes

### Filter by Metadata

```php
// Find companies with specific metadata
$companies = Company::whereMeta('is_partner', true)->get();

// With custom alias
$companies = Company::whereMeta('is_partner', true, 'partner_meta')->get();
```

### Join Metadata Table

```php
// Join metadata for complex queries
$companies = Company::meta()
    ->where('model_meta.key', 'industry_notes')
    ->get();
```

## Type Casting

Metadata values are automatically cast based on their PHP type:

| PHP Type | Storage | Retrieval |
|----------|---------|-----------|
| `string` | Direct | Direct |
| `int` | String | Cast to int |
| `float` | String | Cast to float |
| `bool` | '1' or '0' | Cast to bool |
| `array` | JSON | Decoded array |
| `object` | JSON | Decoded array |
| `null` | NULL | NULL |

## Filament v4.3+ Integration

### Form Fields

```php
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;

Section::make(__('app.labels.metadata'))
    ->schema([
        KeyValue::make('metadata')
            ->label(__('app.labels.custom_metadata'))
            ->keyLabel(__('app.labels.key'))
            ->valueLabel(__('app.labels.value'))
            ->addActionLabel(__('app.actions.add_metadata'))
            ->reorderable()
            ->afterStateUpdated(function ($state, $set, $record): void {
                if ($record && is_array($state)) {
                    app(MetadataService::class)->sync($record, $state);
                }
            }),
    ])
```

### Infolist Display

```php
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;

Section::make(__('app.labels.metadata'))
    ->schema([
        KeyValueEntry::make('metadata')
            ->label(__('app.labels.custom_metadata'))
            ->state(fn ($record) => app(MetadataService::class)->all($record)->all()),
    ])
```

### Table Columns

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('metadata')
    ->label(__('app.labels.metadata_count'))
    ->getStateUsing(fn ($record) => app(MetadataService::class)->all($record)->count())
    ->suffix(' items')
    ->badge()
```

### Actions

```php
use Filament\Actions\Action;
use App\Services\Metadata\MetadataService;

Action::make('addMetadata')
    ->label(__('app.actions.add_metadata'))
    ->form([
        TextInput::make('key')
            ->required()
            ->label(__('app.labels.key')),
        TextInput::make('value')
            ->required()
            ->label(__('app.labels.value')),
    ])
    ->action(function (array $data, $record): void {
        app(MetadataService::class)->set($record, $data['key'], $data['value']);
        
        Notification::make()
            ->title(__('app.notifications.metadata_added'))
            ->success()
            ->send();
    })
```

## Testing

### Unit Tests

```php
use App\Models\Company;
use App\Services\Metadata\MetadataService;

it('can set and get metadata', function (): void {
    $company = Company::factory()->create();
    $service = app(MetadataService::class);
    
    $service->set($company, 'test_key', 'test_value');
    
    expect($service->get($company, 'test_key'))->toBe('test_value');
});

it('can handle default values', function (): void {
    $company = new class extends Company {
        public array $defaultMetaValues = [
            'theme' => 'light',
        ];
    };
    $company->save();
    
    expect($company->getMeta('theme'))->toBe('light');
});

it('removes metadata when set to default value', function (): void {
    $company = new class extends Company {
        public array $defaultMetaValues = [
            'is_verified' => false,
        ];
    };
    $company->save();
    
    $company->setMeta('is_verified', true);
    $company->save();
    expect($company->hasMeta('is_verified'))->toBeTrue();
    
    $company->setMeta('is_verified', false);
    $company->save();
    expect($company->hasMeta('is_verified'))->toBeFalse();
});
```

### Feature Tests

```php
it('can query models by metadata', function (): void {
    $company1 = Company::factory()->create();
    $company1->setMeta('is_partner', true);
    $company1->save();
    
    $company2 = Company::factory()->create();
    $company2->setMeta('is_partner', false);
    $company2->save();
    
    $partners = Company::whereMeta('is_partner', true)->get();
    
    expect($partners)->toHaveCount(1)
        ->and($partners->first()->id)->toBe($company1->id);
});
```

## Performance Considerations

### Eager Loading

Always eager load metadata when accessing multiple models:

```php
// Good
$companies = Company::with('metas')->get();
foreach ($companies as $company) {
    $notes = $company->getMeta('industry_notes');
}

// Bad (N+1 queries)
$companies = Company::all();
foreach ($companies as $company) {
    $notes = $company->getMeta('industry_notes');
}
```

### Caching

For frequently accessed metadata, consider caching:

```php
$notes = Cache::remember(
    "company.{$company->id}.industry_notes",
    3600,
    fn () => $company->getMeta('industry_notes')
);
```

### Indexing

The `model_meta` table has indexes on:
- `metable_type` and `metable_id` (polymorphic)
- `type`
- `key`
- Unique constraint on `(metable_type, metable_id, key)`

## Best Practices

### DO:
- ✅ Use metadata for flexible, optional data
- ✅ Define default values for common states
- ✅ Eager load metadata when accessing multiple models
- ✅ Use the service layer for complex operations
- ✅ Validate metadata values before setting
- ✅ Use query scopes for filtering by metadata
- ✅ Cache frequently accessed metadata

### DON'T:
- ❌ Store critical business data only in metadata
- ❌ Use metadata for data that needs complex queries
- ❌ Forget to save after setting metadata
- ❌ Skip eager loading in loops
- ❌ Store large binary data in metadata
- ❌ Use metadata for relationships
- ❌ Ignore type casting behavior

## Use Cases

### Feature Flags

```php
$company->setMeta('feature_advanced_analytics', true);

if ($company->getMeta('feature_advanced_analytics')) {
    // Show advanced analytics
}
```

### User Preferences

```php
$user->setMeta([
    'theme' => 'dark',
    'language' => 'en',
    'timezone' => 'America/New_York',
    'notifications_enabled' => true,
]);
```

### Tracking Metrics

```php
$metadataService->increment($company, 'profile_views');
$metadataService->increment($company, 'total_revenue', 1500.00);
```

### Temporary Flags

```php
// Mark for review
$company->setMeta('needs_review', true);

// After review
$company->unsetMeta('needs_review');
```

### Integration Data

```php
$company->setMeta([
    'salesforce_id' => 'SF-12345',
    'hubspot_id' => 'HS-67890',
    'last_sync_at' => now()->toISOString(),
]);
```

## Migration from Custom Fields

If migrating from a custom fields system:

```php
// Old custom field
$company->custom_fields()->create([
    'key' => 'industry_notes',
    'value' => 'Tech startup',
]);

// New metadata
$company->setMeta('industry_notes', 'Tech startup');
$company->save();
```

## Troubleshooting

### Metadata Not Persisting

Ensure you call `save()` after setting metadata:

```php
$company->setMeta('key', 'value');
$company->save(); // Required!
```

### Type Casting Issues

Check the `type` column in `model_meta` table to verify correct type detection.

### Query Performance

Use `explain` to check query plans when using metadata scopes:

```php
DB::enableQueryLog();
Company::whereMeta('is_partner', true)->get();
dd(DB::getQueryLog());
```

## Related Documentation

- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/laravel-conventions.md` - Model conventions
- `.kiro/steering/filament-conventions.md` - Filament integration patterns
- `.kiro/steering/testing-standards.md` - Testing requirements

## References

- Package Repository: https://github.com/kodeine/laravel-meta
- Original Package Documentation: https://github.com/kodeine/laravel-meta/blob/master/README.md
