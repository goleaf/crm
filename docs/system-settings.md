# System Settings Implementation

## Overview

The System Settings module provides a flexible, type-safe configuration management system for the CRM application. It supports global and team-specific settings with caching, encryption, and a user-friendly Filament interface.

## Features

### Core Capabilities

1. **Type-Safe Settings**: Support for string, integer, float, boolean, JSON, and array types
2. **Team-Specific Settings**: Settings can be global or scoped to specific teams
3. **Encryption**: Sensitive settings can be encrypted at rest
4. **Caching**: Automatic caching with cache invalidation on updates
5. **Grouping**: Settings organized by functional groups (company, locale, currency, etc.)
6. **Public Settings**: Some settings can be marked as publicly accessible

### Setting Groups

- **general**: General application settings
- **company**: Company information (name, address, contact details)
- **locale**: Localization settings (language, timezone, date/time formats)
- **currency**: Currency and exchange rate settings
- **fiscal**: Fiscal year configuration
- **business_hours**: Business hours and holidays
- **email**: Email configuration
- **scheduler**: Scheduler/cron settings
- **notification**: Notification defaults

## Database Schema

```sql
CREATE TABLE settings (
    id BIGINT PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    type VARCHAR(255) DEFAULT 'string',
    group VARCHAR(255) DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT FALSE,
    team_id BIGINT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX (group, key),
    INDEX (team_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);
```

## Usage

### Using SettingsService

```php
use App\Services\SettingsService;

$settings = app(SettingsService::class);

// Get a setting
$value = $settings->get('company.name', 'Default Company');

// Set a setting
$settings->set('company.name', 'Acme Corp', 'string', 'company');

// Set an encrypted setting
$settings->set('api.secret_key', 'secret123', 'string', 'general', null, true);

// Get team-specific setting
$value = $settings->get('company.name', null, $teamId);

// Get all settings in a group
$companySettings = $settings->getGroup('company');

// Set multiple settings
$settings->setMany([
    'locale.language' => 'en',
    'locale.timezone' => 'America/New_York',
], 'locale');

// Delete a setting
$settings->delete('old.setting');

// Check if setting exists
if ($settings->has('company.name')) {
    // ...
}
```

### Helper Methods

```php
// Get company information
$companyInfo = $settings->getCompanyInfo();
// Returns: ['name', 'legal_name', 'tax_id', 'address', 'phone', 'email', 'website', 'logo_url']

// Get locale settings
$localeSettings = $settings->getLocaleSettings();
// Returns: ['locale', 'timezone', 'date_format', 'time_format', 'first_day_of_week']

// Get currency settings
$currencySettings = $settings->getCurrencySettings();
// Returns: ['default_currency', 'exchange_rates', 'auto_update_rates']

// Get fiscal year settings
$fiscalSettings = $settings->getFiscalYearSettings();
// Returns: ['start_month', 'start_day']

// Get business hours
$businessHours = $settings->getBusinessHours();
// Returns: ['monday', 'tuesday', ..., 'sunday'] with start/end times

// Get notification defaults
$notificationDefaults = $settings->getNotificationDefaults();
// Returns: ['email_enabled', 'database_enabled', 'slack_enabled', 'slack_webhook']
```

### Using the Setting Model Directly

```php
use App\Models\Setting;

// Create a setting
$setting = Setting::create([
    'key' => 'app.feature_flag',
    'type' => 'boolean',
    'group' => 'general',
]);
$setting->setValue(true);
$setting->save();

// Get typed value
$value = $setting->getValue(); // Returns boolean true

// Query settings
$companySettings = Setting::where('group', 'company')->get();
```

## Filament Resource

The system includes a Filament resource for managing settings through the admin panel:

- **Location**: `/admin/settings`
- **Features**:
  - Create, edit, and delete settings
  - Filter by group, type, public/encrypted status
  - Search by key
  - Team-specific settings support
  - Inline help text for each field

## Default Settings

The `SystemSettingsSeeder` populates the following default settings:

### Company Information
- `company.name`: Application name
- `company.legal_name`: Legal company name
- `company.tax_id`: Tax identification number
- `company.address`: Company address
- `company.phone`: Company phone
- `company.email`: Company email
- `company.website`: Company website
- `company.logo_url`: Logo URL

### Locale Settings
- `locale.language`: Default language (en)
- `locale.timezone`: Default timezone (UTC)
- `locale.date_format`: Date format (Y-m-d)
- `locale.time_format`: Time format (H:i:s)
- `locale.first_day_of_week`: First day of week (0 = Sunday)

### Currency Settings
- `currency.default`: Default currency (USD)
- `currency.exchange_rates`: Exchange rates (empty array)
- `currency.auto_update_rates`: Auto-update rates (false)

### Fiscal Year
- `fiscal.start_month`: Fiscal year start month (1 = January)
- `fiscal.start_day`: Fiscal year start day (1)

### Business Hours
- `business_hours.monday` through `business_hours.friday`: 09:00-17:00
- `business_hours.saturday` and `business_hours.sunday`: null (closed)
- `business_hours.holidays`: Empty array

### Email Settings
- `email.from_address`: From email address
- `email.from_name`: From name
- `email.reply_to`: Reply-to address

### Notification Defaults
- `notifications.email_enabled`: true
- `notifications.database_enabled`: true
- `notifications.slack_enabled`: false
- `notifications.slack_webhook`: Empty

### Scheduler Settings
- `scheduler.enabled`: true
- `scheduler.timezone`: UTC

## Testing

### Unit Tests

Located in `tests/Unit/Services/SettingsServiceTest.php`:

- String, integer, boolean, and array type handling
- Default values
- Setting updates
- Deletion
- Group queries
- Batch operations
- Caching behavior
- Team-specific settings
- Encrypted settings
- Helper methods

### Property-Based Tests

Located in `tests/Unit/Properties/ConfigurationPersistencePropertyTest.php`:

**Property 1: Configuration persistence** (Validates Requirements 1.1, 1.2)

Tests that settings persist correctly across cache clears and restarts:
- Random settings with different types persist correctly
- Team-specific settings persist independently
- Encrypted settings persist securely
- Settings grouped correctly
- Updates don't create duplicates
- Complex nested arrays persist with full structure

Run tests with:
```bash
php artisan test --filter=SettingsServiceTest
php artisan test --filter=ConfigurationPersistencePropertyTest
```

## Installation

1. Run the migration:
```bash
php artisan migrate
```

2. Seed default settings:
```bash
php artisan db:seed --class=SystemSettingsSeeder
```

3. Access the settings interface at `/admin/settings`

## Caching

Settings are automatically cached for 1 hour (3600 seconds). The cache is cleared when:
- A setting is updated
- A setting is deleted
- `clearCache()` is called explicitly

Cache keys follow the pattern:
- Global: `settings:global:{key}`
- Team-specific: `settings:team:{team_id}:{key}`

## Security Considerations

1. **Encryption**: Use `is_encrypted` for sensitive data (API keys, passwords)
2. **Public Settings**: Only mark settings as public if they need to be accessed without authentication
3. **Team Isolation**: Team-specific settings are automatically scoped
4. **Validation**: Always validate setting values before storing
5. **Access Control**: Use Filament policies to control who can manage settings

## Best Practices

1. **Naming Convention**: Use dot notation for hierarchical keys (e.g., `company.name`, `locale.timezone`)
2. **Type Safety**: Always specify the correct type when setting values
3. **Grouping**: Use appropriate groups for logical organization
4. **Documentation**: Add descriptions to settings for clarity
5. **Defaults**: Always provide sensible defaults when getting settings
6. **Caching**: Leverage the built-in caching for performance
7. **Team Context**: Consider whether settings should be global or team-specific

## Future Enhancements

Potential improvements for future iterations:

1. Setting validation rules
2. Setting change history/audit log
3. Import/export settings
4. Setting templates
5. Environment-specific settings
6. Setting dependencies
7. UI for managing exchange rates
8. Holiday calendar management
9. Business hours calculator
10. Setting search and bulk operations

## Related Requirements

This implementation satisfies:
- **Requirement 1.1**: Manage company info, locale, date/time formats, currencies/exchange rates, fiscal year, business hours, holidays
- **Requirement 1.2**: Configure email settings/system accounts, notification defaults, scheduler/cron jobs

## Correctness Property

**Property 1: Configuration persistence**

System settings persist across restarts and apply consistently across modules. This is validated through property-based tests that verify:
- Settings with any valid key, value, type, and group persist correctly
- Cache clears don't affect persistence
- Team-specific settings remain isolated
- Encrypted settings decrypt correctly
- Complex data structures maintain integrity
