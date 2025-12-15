# System Settings Quick Reference

## Quick Start

```php
// Get a setting
$value = setting('company.name', 'Default');

// Set a setting
setting()->set('company.name', 'Acme Corp', 'string', 'company');

// Team-specific setting
$value = team_setting('company.name');
```

## Common Operations

### Get Settings

```php
// Simple get
$name = setting('company.name');

// With default
$email = setting('company.email', 'default@example.com');

// Team-specific
$teamName = team_setting('company.name', null, $teamId);

// Get all in group
$companySettings = setting()->getGroup('company');
```

### Set Settings

```php
// String
setting()->set('company.name', 'Acme Corp', 'string', 'company');

// Integer
setting()->set('fiscal.start_month', 7, 'integer', 'fiscal');

// Boolean
setting()->set('notifications.email_enabled', true, 'boolean', 'notification');

// Array
setting()->set('business_hours.monday', ['start' => '09:00', 'end' => '17:00'], 'array', 'business_hours');

// Encrypted
setting()->set('api.secret', 'secret123', 'string', 'general', null, true);

// Team-specific
setting()->set('company.name', 'Team Name', 'string', 'company', $teamId);
```

### Helper Methods

```php
// Company info
$info = setting()->getCompanyInfo();
// Returns: name, legal_name, tax_id, address, phone, email, website, logo_url

// Locale settings
$locale = setting()->getLocaleSettings();
// Returns: locale, timezone, date_format, time_format, first_day_of_week

// Currency settings
$currency = setting()->getCurrencySettings();
// Returns: default_currency, exchange_rates, auto_update_rates

// Fiscal year
$fiscal = setting()->getFiscalYearSettings();
// Returns: start_month, start_day

// Business hours
$hours = setting()->getBusinessHours();
// Returns: monday-sunday with start/end times

// Notifications
$notifications = setting()->getNotificationDefaults();
// Returns: email_enabled, database_enabled, slack_enabled, slack_webhook
```

## Setting Types

| Type | Example Value | Use Case |
|------|---------------|----------|
| `string` | `"Acme Corp"` | Text values |
| `integer` | `42` | Whole numbers |
| `float` | `3.14` | Decimal numbers |
| `boolean` | `true` | Yes/No flags |
| `json` | `{"key": "value"}` | Complex objects |
| `array` | `["item1", "item2"]` | Lists |

## Setting Groups

| Group | Purpose | Example Keys |
|-------|---------|--------------|
| `general` | General settings | `app.feature_flag` |
| `company` | Company info | `company.name`, `company.email` |
| `locale` | Localization | `locale.timezone`, `locale.language` |
| `currency` | Currency | `currency.default`, `currency.exchange_rates` |
| `fiscal` | Fiscal year | `fiscal.start_month` |
| `business_hours` | Hours/holidays | `business_hours.monday` |
| `email` | Email config | `email.from_address` |
| `scheduler` | Cron/scheduler | `scheduler.enabled` |
| `notification` | Notifications | `notifications.email_enabled` |

## Default Settings

### Company
- `company.name` - Company name
- `company.email` - Company email
- `company.phone` - Company phone
- `company.website` - Company website

### Locale
- `locale.language` - Language (en)
- `locale.timezone` - Timezone (UTC)
- `locale.date_format` - Date format (Y-m-d)
- `locale.time_format` - Time format (H:i:s)

### Currency
- `currency.default` - Default currency (USD)
- `currency.exchange_rates` - Exchange rates (array)

### Business Hours
- `business_hours.monday` - Monday hours
- `business_hours.tuesday` - Tuesday hours
- ... (through sunday)

### Notifications
- `notifications.email_enabled` - Email notifications (true)
- `notifications.database_enabled` - Database notifications (true)
- `notifications.slack_enabled` - Slack notifications (false)

## Admin UI

Access settings management at: `/admin/settings`

Features:
- Create/edit/delete settings
- Filter by group, type
- Search by key
- Team-specific settings
- Encryption support

## Testing

```bash
# Run unit tests
php artisan test --filter=SettingsServiceTest

# Run property tests
php artisan test --filter=ConfigurationPersistencePropertyTest

# Run all settings tests
php artisan test --filter=Settings
```

## Installation

```bash
# Run migration
php artisan migrate

# Seed defaults
php artisan db:seed --class=SystemSettingsSeeder
```

## Caching

- Settings are cached for 1 hour
- Cache automatically cleared on updates
- Manual clear: `setting()->clearCache()`

## Security

- Use `is_encrypted` for sensitive data
- Only mark as `is_public` if needed without auth
- Team settings are automatically scoped
- Validate values before storing

## Best Practices

1. Use dot notation: `company.name`, `locale.timezone`
2. Specify correct type when setting
3. Use appropriate groups
4. Provide defaults when getting
5. Consider team vs global scope
6. Encrypt sensitive data
7. Add descriptions for clarity

## Common Patterns

```php
// Feature flags
if (setting('features.new_dashboard', false)) {
    // Show new dashboard
}

// Localization
$timezone = setting('locale.timezone', config('app.timezone'));
app()->setTimezone($timezone);

// Email configuration
$fromAddress = setting('email.from_address', config('mail.from.address'));
$fromName = setting('email.from_name', config('mail.from.name'));

// Business logic
$fiscalStartMonth = setting('fiscal.start_month', 1);
$fiscalYear = now()->month >= $fiscalStartMonth 
    ? now()->year 
    : now()->year - 1;
```

## Troubleshooting

**Setting not persisting?**
- Check cache: `setting()->clearCache()`
- Verify type matches value
- Check team_id if using team settings

**Can't access setting?**
- Verify key exists: `setting()->has('key')`
- Check default value
- Verify team context

**Encrypted setting not decrypting?**
- Ensure `is_encrypted` is true
- Check APP_KEY is set
- Verify encryption hasn't changed

## More Information

See full documentation: `docs/system-settings.md`
