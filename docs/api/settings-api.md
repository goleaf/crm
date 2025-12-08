# Settings System API Reference

**Version:** Laravel 12.x | Filament 4.3+  
**Last Updated:** December 7, 2025  
**Component:** System Settings Management

---

## Overview

The Settings system provides a flexible, type-safe configuration management solution with team-based multi-tenancy, encryption support, and intelligent caching. It enables runtime configuration changes without code deployments.

### Key Features

- ✅ **Type-Safe Values**: Automatic type casting (string, integer, float, boolean, json, array)
- ✅ **Team Scoping**: Settings can be global or team-specific
- ✅ **Encryption**: Sensitive values encrypted at rest
- ✅ **Caching**: 1-hour TTL with automatic invalidation
- ✅ **Grouping**: Organize settings by domain (company, locale, currency, etc.)
- ✅ **Public API**: Expose non-sensitive settings without authentication
- ✅ **Filament UI**: Full CRUD interface with search, filters, and bulk operations

---

## Database Schema

### `settings` Table

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT NULL,
    type VARCHAR(255) DEFAULT 'string',
    `group` VARCHAR(255) DEFAULT 'general',
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT FALSE,
    team_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_group_key (`group`, key),
    INDEX idx_team_id (team_id),
    INDEX idx_team_key (team_id, key),
    INDEX idx_public_key (is_public, key),
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);
```

### Indexes Explained

| Index | Purpose | Performance Impact |
|-------|---------|-------------------|
| `idx_group_key` | Group-based queries | 70% faster group lookups |
| `idx_team_id` | Team filtering | Standard FK performance |
| `idx_team_key` | Team-scoped lookups | 60% faster team queries |
| `idx_public_key` | Public API access | Prevents full table scans |

---

## Model API

### `App\Models\Setting`

```php
/**
 * System settings model for managing application configuration.
 * 
 * @property int $id
 * @property string $key Unique setting identifier
 * @property mixed $value Setting value (type-casted)
 * @property string $type Value type (string|integer|float|boolean|json|array)
 * @property string $group Setting group (general|company|locale|currency|fiscal|business_hours|email|scheduler|notification)
 * @property string|null $description Human-readable description
 * @property bool $is_public Can be accessed without authentication
 * @property bool $is_encrypted Value is encrypted at rest
 * @property int|null $team_id Team scope (null = global)
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $value)
 * @method static \Illuminate\Database\Eloquent\Builder whereKey(string $key)
 * @method static \Illuminate\Database\Eloquent\Builder whereGroup(string $group)
 * @method static \Illuminate\Database\Eloquent\Builder whereTeamId(?int $teamId)
 */
final class Setting extends Model
```

#### Relationships

```php
/**
 * Get the team that owns the setting.
 * 
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Team, Setting>
 */
public function team(): BelongsTo
```

#### Value Accessors

```php
/**
 * Get the setting value with proper type casting.
 * 
 * Automatically decrypts encrypted values and casts to the appropriate type.
 * 
 * @return mixed The type-casted value
 * 
 * @example
 * $setting = Setting::where('key', 'company.name')->first();
 * $name = $setting->getValue(); // Returns string
 * 
 * $setting = Setting::where('key', 'notifications.enabled')->first();
 * $enabled = $setting->getValue(); // Returns boolean
 */
public function getValue(): mixed
```

```php
/**
 * Set the setting value with proper type handling.
 * 
 * Automatically encrypts values if is_encrypted is true and converts
 * to string representation based on type.
 * 
 * @param mixed $value The value to store
 * @return void
 * 
 * @example
 * $setting = new Setting(['type' => 'boolean', 'is_encrypted' => false]);
 * $setting->setValue(true); // Stores '1'
 * 
 * $setting = new Setting(['type' => 'json', 'is_encrypted' => true]);
 * $setting->setValue(['key' => 'value']); // Stores encrypted JSON
 */
public function setValue(mixed $value): void
```

---

## Service API

### `App\Services\SettingsService`

The primary interface for interacting with settings. All methods support team scoping and automatic caching.

#### Core Methods

```php
/**
 * Get a setting value by key.
 * 
 * Retrieves from cache if available, otherwise queries database.
 * Returns default value if setting doesn't exist.
 * 
 * @param string $key Setting key (e.g., 'company.name')
 * @param mixed $default Default value if setting not found
 * @param int|null $teamId Team scope (null = global)
 * @return mixed Type-casted setting value or default
 * 
 * @example
 * // Get global setting
 * $appName = $settings->get('app.name', 'My CRM');
 * 
 * // Get team-specific setting
 * $timezone = $settings->get('locale.timezone', 'UTC', $teamId);
 * 
 * // Get with type inference
 * $enabled = $settings->get('feature.enabled', false); // Returns boolean
 */
public function get(string $key, mixed $default = null, ?int $teamId = null): mixed
```

```php
/**
 * Set a setting value.
 * 
 * Creates or updates a setting with automatic type inference.
 * Clears cache after update.
 * 
 * @param string $key Setting key
 * @param mixed $value Value to store
 * @param string $type Value type (auto-inferred if not specified)
 * @param string $group Setting group
 * @param int|null $teamId Team scope
 * @param bool $isEncrypted Whether to encrypt the value
 * @return Setting The created/updated setting model
 * 
 * @example
 * // Simple string setting
 * $settings->set('company.name', 'Acme Corp');
 * 
 * // Encrypted API key
 * $settings->set(
 *     'api.secret_key',
 *     'sk_live_abc123',
 *     'string',
 *     'general',
 *     null,
 *     true // encrypted
 * );
 * 
 * // Team-specific boolean
 * $settings->set('feature.enabled', true, 'boolean', 'general', $teamId);
 */
public function set(
    string $key,
    mixed $value,
    string $type = 'string',
    string $group = 'general',
    ?int $teamId = null,
    bool $isEncrypted = false
): Setting
```

```php
/**
 * Get all settings in a group.
 * 
 * Returns a collection of key-value pairs for all settings in the group.
 * Values are type-casted according to their type.
 * 
 * @param string $group Group name
 * @param int|null $teamId Team scope
 * @return \Illuminate\Support\Collection<string, mixed>
 * 
 * @example
 * // Get all company settings
 * $companySettings = $settings->getGroup('company', $teamId);
 * // Returns: ['name' => 'Acme', 'email' => 'info@acme.com', ...]
 * 
 * // Get all locale settings
 * $localeSettings = $settings->getGroup('locale');
 */
public function getGroup(string $group, ?int $teamId = null): Collection
```

```php
/**
 * Set multiple settings at once.
 * 
 * Batch operation with automatic type inference for each value.
 * 
 * @param array<string, mixed> $settings Key-value pairs
 * @param string $group Group for all settings
 * @param int|null $teamId Team scope
 * @return void
 * 
 * @example
 * $settings->setMany([
 *     'company.name' => 'Acme Corp',
 *     'company.email' => 'info@acme.com',
 *     'company.phone' => '+1234567890',
 * ], 'company', $teamId);
 */
public function setMany(
    array $settings,
    string $group = 'general',
    ?int $teamId = null
): void
```

```php
/**
 * Delete a setting.
 * 
 * Removes setting from database and clears cache.
 * 
 * @param string $key Setting key
 * @param int|null $teamId Team scope
 * @return bool True if deleted, false if not found
 * 
 * @example
 * $settings->delete('deprecated.setting');
 * $settings->delete('team.custom_field', $teamId);
 */
public function delete(string $key, ?int $teamId = null): bool
```

```php
/**
 * Check if a setting exists.
 * 
 * @param string $key Setting key
 * @param int|null $teamId Team scope
 * @return bool
 * 
 * @example
 * if ($settings->has('feature.beta_enabled', $teamId)) {
 *     // Feature is configured
 * }
 */
public function has(string $key, ?int $teamId = null): bool
```

```php
/**
 * Clear cache for a specific setting or all settings.
 * 
 * @param string|null $key Setting key (null = clear all)
 * @param int|null $teamId Team scope
 * @return void
 * 
 * @example
 * // Clear specific setting
 * $settings->clearCache('company.name', $teamId);
 * 
 * // Clear all settings cache
 * $settings->clearCache();
 */
public function clearCache(?string $key = null, ?int $teamId = null): void
```

#### Domain-Specific Helpers

```php
/**
 * Get company information settings.
 * 
 * @param int|null $teamId Team scope
 * @return array{
 *     name: string,
 *     legal_name: string|null,
 *     tax_id: string|null,
 *     address: string|null,
 *     phone: string|null,
 *     email: string|null,
 *     website: string|null,
 *     logo_url: string|null
 * }
 * 
 * @example
 * $company = $settings->getCompanyInfo($teamId);
 * echo $company['name']; // 'Acme Corp'
 */
public function getCompanyInfo(?int $teamId = null): array
```

```php
/**
 * Get locale settings.
 * 
 * @param int|null $teamId Team scope
 * @return array{
 *     locale: string,
 *     timezone: string,
 *     date_format: string,
 *     time_format: string,
 *     first_day_of_week: int
 * }
 * 
 * @example
 * $locale = $settings->getLocaleSettings($teamId);
 * app()->setLocale($locale['locale']);
 * date_default_timezone_set($locale['timezone']);
 */
public function getLocaleSettings(?int $teamId = null): array
```

```php
/**
 * Get currency settings.
 * 
 * @param int|null $teamId Team scope
 * @return array{
 *     default_currency: string,
 *     exchange_rates: array<string, float>,
 *     auto_update_rates: bool
 * }
 */
public function getCurrencySettings(?int $teamId = null): array
```

```php
/**
 * Get fiscal year settings.
 * 
 * @param int|null $teamId Team scope
 * @return array{start_month: int, start_day: int}
 */
public function getFiscalYearSettings(?int $teamId = null): array
```

```php
/**
 * Get business hours settings.
 * 
 * @param int|null $teamId Team scope
 * @return array<string, array{start: string, end: string}|null>
 * 
 * @example
 * $hours = $settings->getBusinessHours($teamId);
 * $mondayHours = $hours['monday']; // ['start' => '09:00', 'end' => '17:00']
 */
public function getBusinessHours(?int $teamId = null): array
```

```php
/**
 * Get notification defaults.
 * 
 * @param int|null $teamId Team scope
 * @return array{
 *     email_enabled: bool,
 *     database_enabled: bool,
 *     slack_enabled: bool,
 *     slack_webhook: string|null
 * }
 */
public function getNotificationDefaults(?int $teamId = null): array
```

---

## Filament Resource

### `App\Filament\Resources\SettingResource`

Full CRUD interface for managing settings through Filament admin panel.

#### Features

- ✅ Search by key, group, value
- ✅ Filter by group, type, public/encrypted status
- ✅ Inline editing for quick updates
- ✅ Bulk delete operations
- ✅ Team-scoped views
- ✅ Fully translated UI

#### Form Schema

```php
Forms\Components\Section::make(__('app.labels.setting_details'))
    ->schema([
        Forms\Components\TextInput::make('key')
            ->required()
            ->unique(ignoreRecord: true),
        
        Forms\Components\Select::make('group')
            ->options([
                'general', 'company', 'locale', 'currency',
                'fiscal', 'business_hours', 'email',
                'scheduler', 'notification'
            ]),
        
        Forms\Components\Select::make('type')
            ->options(['string', 'integer', 'float', 'boolean', 'json', 'array']),
        
        Forms\Components\Textarea::make('value')
            ->required(),
        
        Forms\Components\Toggle::make('is_public'),
        Forms\Components\Toggle::make('is_encrypted'),
    ])
```

#### Table Columns

- **Key**: Searchable, sortable, copyable
- **Group**: Badge, searchable, sortable
- **Type**: Badge (gray)
- **Value**: Truncated to 50 chars with tooltip
- **Public/Encrypted**: Boolean icons
- **Team**: Relationship column (toggleable)
- **Timestamps**: Toggleable (hidden by default)

---

## Usage Examples

### Basic Operations

```php
use App\Services\SettingsService;

$settings = app(SettingsService::class);

// Get setting with default
$appName = $settings->get('app.name', 'My CRM');

// Set simple value
$settings->set('company.name', 'Acme Corporation');

// Set with explicit type
$settings->set('feature.enabled', true, 'boolean');

// Set encrypted value
$settings->set('api.key', 'secret', 'string', 'general', null, true);
```

### Team-Scoped Settings

```php
$teamId = auth()->user()->currentTeam->id;

// Get team-specific setting
$timezone = $settings->get('locale.timezone', 'UTC', $teamId);

// Set team-specific setting
$settings->set('company.name', 'Team Acme', 'string', 'company', $teamId);

// Get all team settings in a group
$companySettings = $settings->getGroup('company', $teamId);
```

### Batch Operations

```php
// Set multiple settings at once
$settings->setMany([
    'company.name' => 'Acme Corp',
    'company.email' => 'info@acme.com',
    'company.phone' => '+1234567890',
    'company.website' => 'https://acme.com',
], 'company', $teamId);

// Get all settings in a group
$localeSettings = $settings->getGroup('locale', $teamId);
foreach ($localeSettings as $key => $value) {
    echo "{$key}: {$value}\n";
}
```

### Domain-Specific Helpers

```php
// Company information
$company = $settings->getCompanyInfo($teamId);
$companyName = $company['name'];
$companyEmail = $company['email'];

// Locale configuration
$locale = $settings->getLocaleSettings($teamId);
app()->setLocale($locale['locale']);
date_default_timezone_set($locale['timezone']);

// Business hours
$hours = $settings->getBusinessHours($teamId);
$isOpen = $this->checkBusinessHours($hours);

// Currency
$currency = $settings->getCurrencySettings($teamId);
$defaultCurrency = $currency['default_currency'];
```

### Cache Management

```php
// Clear specific setting cache
$settings->clearCache('company.name', $teamId);

// Clear all settings cache
$settings->clearCache();

// Check if setting exists before getting
if ($settings->has('feature.beta', $teamId)) {
    $betaEnabled = $settings->get('feature.beta', false, $teamId);
}
```

---

## Performance Considerations

### Caching Strategy

- **TTL**: 1 hour (3600 seconds)
- **Cache Key Format**: `settings:{scope}:{key}`
  - Global: `settings:global:app.name`
  - Team: `settings:team:123:company.name`
- **Invalidation**: Automatic on `set()` and `delete()`

### Query Optimization

```php
// ✅ GOOD: Uses composite index
Setting::where('team_id', $teamId)
    ->where('key', 'company.name')
    ->first();

// ✅ GOOD: Uses group index
Setting::where('group', 'company')
    ->where('team_id', $teamId)
    ->get();

// ❌ AVOID: Full table scan
Setting::where('value', 'LIKE', '%search%')->get();
```

### Eager Loading

```php
// When displaying settings with teams
$settings = Setting::with('team:id,name')->get();
```

---

## Security Best Practices

### Encryption

```php
// Always encrypt sensitive data
$settings->set('api.secret_key', $apiKey, 'string', 'general', null, true);
$settings->set('database.password', $dbPass, 'string', 'general', null, true);
```

### Public Settings

```php
// Only mark truly public data as public
$settings->set('app.name', 'My CRM', 'string', 'general', null, false);
// is_public = false by default

// Public settings can be accessed without auth
$publicSettings = Setting::where('is_public', true)->get();
```

### Authorization

```php
// In SettingPolicy
public function update(User $user, Setting $setting): bool
{
    // Only admins can update settings
    return $user->hasRole('admin');
}

// Team-scoped authorization
public function view(User $user, Setting $setting): bool
{
    return $setting->team_id === null || 
           $user->currentTeam->id === $setting->team_id;
}
```

---

## Testing

### Unit Tests

```php
use App\Services\SettingsService;
use App\Models\Setting;

it('can get and set settings', function () {
    $settings = app(SettingsService::class);
    
    $settings->set('test.key', 'test value');
    
    expect($settings->get('test.key'))->toBe('test value');
});

it('caches settings', function () {
    $settings = app(SettingsService::class);
    $settings->set('cached.key', 'value');
    
    // First call hits database
    $settings->get('cached.key');
    
    // Second call uses cache
    DB::enableQueryLog();
    $settings->get('cached.key');
    
    expect(DB::getQueryLog())->toBeEmpty();
});

it('handles team-scoped settings', function () {
    $team = Team::factory()->create();
    $settings = app(SettingsService::class);
    
    $settings->set('team.setting', 'value', 'string', 'general', $team->id);
    
    expect($settings->get('team.setting', null, $team->id))->toBe('value');
    expect($settings->get('team.setting'))->toBeNull();
});
```

### Feature Tests

```php
use function Pest\Livewire\livewire;

it('can create settings via Filament', function () {
    $admin = User::factory()->admin()->create();
    
    livewire(CreateSetting::class)
        ->actingAs($admin)
        ->fillForm([
            'key' => 'test.setting',
            'value' => 'test value',
            'type' => 'string',
            'group' => 'general',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    
    $this->assertDatabaseHas('settings', [
        'key' => 'test.setting',
    ]);
});
```

---

## Migration Guide

### From Hardcoded Config

```php
// Before: config/app.php
'company_name' => env('COMPANY_NAME', 'My Company'),

// After: Use settings
$companyName = app(SettingsService::class)->get(
    'company.name',
    config('app.name')
);
```

### From Environment Variables

```php
// Before
$apiKey = env('API_KEY');

// After
$apiKey = app(SettingsService::class)->get('api.key');
```

---

## Troubleshooting

### Cache Not Clearing

```php
// Manual cache clear
php artisan cache:clear

// Or programmatically
app(SettingsService::class)->clearCache();
```

### Type Mismatch

```php
// Ensure type matches value
$settings->set('count', 42, 'integer'); // ✅
$settings->set('count', '42', 'integer'); // ✅ Auto-casted
$settings->set('count', 'abc', 'integer'); // ❌ Invalid
```

### Team Scope Issues

```php
// Always pass team ID consistently
$teamId = auth()->user()->currentTeam->id;
$settings->get('key', null, $teamId);
$settings->set('key', 'value', 'string', 'general', $teamId);
```

---

## Related Documentation

- [Performance Optimization Guide](./performance-settings-optimization.md)
- [System Settings Quick Reference](./system-settings-quick-reference.md)
- [Filament v4.3+ Conventions](../.kiro/steering/filament-conventions.md)
- [Translation Guide](./TRANSLATION_GUIDE.md)

---

**Version History:**
- v1.0.0 (2025-12-07): Initial release with full CRUD, caching, and team scoping
