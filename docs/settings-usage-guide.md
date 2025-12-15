# Settings System Usage Guide

**Audience:** Developers, System Administrators  
**Last Updated:** December 7, 2025

---

## Table of Contents

1. [Introduction](#introduction)
2. [Quick Start](#quick-start)
3. [Common Use Cases](#common-use-cases)
4. [Best Practices](#best-practices)
5. [Advanced Patterns](#advanced-patterns)
6. [Troubleshooting](#troubleshooting)

---

## Introduction

The Settings system provides a flexible way to manage application configuration that can be changed at runtime without code deployments. It supports:

- **Multi-tenancy**: Global and team-specific settings
- **Type safety**: Automatic type casting for common data types
- **Security**: Encryption for sensitive values
- **Performance**: Intelligent caching with automatic invalidation
- **Organization**: Group settings by domain (company, locale, currency, etc.)

---

## Quick Start

### Installation

The settings system is included by default. Run migrations:

```bash
php artisan migrate
```

### Basic Usage

```php
use App\Services\SettingsService;

// Get the service
$settings = app(SettingsService::class);

// Get a setting
$companyName = $settings->get('company.name', 'Default Company');

// Set a setting
$settings->set('company.name', 'Acme Corporation');

// Check if exists
if ($settings->has('feature.enabled')) {
    // Setting exists
}

// Delete a setting
$settings->delete('deprecated.setting');
```

### Using in Controllers

```php
namespace App\Http\Controllers;

use App\Services\SettingsService;

class DashboardController extends Controller
{
    public function __construct(
        private SettingsService $settings
    ) {}
    
    public function index()
    {
        $companyInfo = $this->settings->getCompanyInfo(
            auth()->user()->currentTeam->id
        );
        
        return view('dashboard', [
            'company' => $companyInfo,
        ]);
    }
}
```

### Using in Blade Templates

```blade
@php
    $settings = app(\App\Services\SettingsService::class);
    $companyName = $settings->get('company.name', config('app.name'));
@endphp

<h1>Welcome to {{ $companyName }}</h1>
```

### Using in Livewire Components

```php
namespace App\Livewire;

use App\Services\SettingsService;
use Livewire\Component;

class CompanyProfile extends Component
{
    public string $companyName;
    
    public function mount(SettingsService $settings)
    {
        $teamId = auth()->user()->currentTeam->id;
        $this->companyName = $settings->get('company.name', '', $teamId);
    }
    
    public function save(SettingsService $settings)
    {
        $teamId = auth()->user()->currentTeam->id;
        $settings->set('company.name', $this->companyName, 'string', 'company', $teamId);
        
        $this->dispatch('notify', 'Company name updated!');
    }
}
```

---

## Common Use Cases

### 1. Company Information

```php
// Set company details
$settings->setMany([
    'company.name' => 'Acme Corporation',
    'company.legal_name' => 'Acme Corp LLC',
    'company.tax_id' => '12-3456789',
    'company.email' => 'info@acme.com',
    'company.phone' => '+1 (555) 123-4567',
    'company.website' => 'https://acme.com',
    'company.address' => '123 Main St, City, State 12345',
], 'company', $teamId);

// Get all company info
$company = $settings->getCompanyInfo($teamId);

// Use in invoice generation
$invoice->setCompanyInfo([
    'name' => $company['name'],
    'address' => $company['address'],
    'tax_id' => $company['tax_id'],
]);
```

### 2. Localization Settings

```php
// Set locale preferences
$settings->setMany([
    'locale.language' => 'en',
    'locale.timezone' => 'America/New_York',
    'locale.date_format' => 'Y-m-d',
    'locale.time_format' => 'H:i:s',
    'locale.first_day_of_week' => 0, // Sunday
], 'locale', $teamId);

// Apply locale settings
$locale = $settings->getLocaleSettings($teamId);
app()->setLocale($locale['locale']);
date_default_timezone_set($locale['timezone']);

// Use in date formatting
$formattedDate = now()->format($locale['date_format']);
```

### 3. Currency Configuration

```php
// Set currency settings
$settings->set('currency.default', 'USD', 'string', 'currency', $teamId);
$settings->set('currency.exchange_rates', [
    'EUR' => 0.85,
    'GBP' => 0.73,
    'JPY' => 110.50,
], 'json', 'currency', $teamId);

// Get currency settings
$currency = $settings->getCurrencySettings($teamId);
$defaultCurrency = $currency['default_currency'];

// Convert amounts
$amount = 100;
$rate = $currency['exchange_rates']['EUR'] ?? 1;
$convertedAmount = $amount * $rate;
```

### 4. Business Hours

```php
// Set business hours
$settings->setMany([
    'business_hours.monday' => ['start' => '09:00', 'end' => '17:00'],
    'business_hours.tuesday' => ['start' => '09:00', 'end' => '17:00'],
    'business_hours.wednesday' => ['start' => '09:00', 'end' => '17:00'],
    'business_hours.thursday' => ['start' => '09:00', 'end' => '17:00'],
    'business_hours.friday' => ['start' => '09:00', 'end' => '17:00'],
    'business_hours.saturday' => null, // Closed
    'business_hours.sunday' => null, // Closed
], 'business_hours', $teamId);

// Check if currently open
function isBusinessOpen(SettingsService $settings, int $teamId): bool
{
    $hours = $settings->getBusinessHours($teamId);
    $day = strtolower(now()->format('l'));
    $dayHours = $hours[$day] ?? null;
    
    if (!$dayHours) {
        return false; // Closed today
    }
    
    $now = now()->format('H:i');
    return $now >= $dayHours['start'] && $now <= $dayHours['end'];
}
```

### 5. Feature Flags

```php
// Enable/disable features
$settings->set('feature.beta_dashboard', true, 'boolean', 'general', $teamId);
$settings->set('feature.ai_suggestions', false, 'boolean', 'general', $teamId);

// Check feature flags
if ($settings->get('feature.beta_dashboard', false, $teamId)) {
    return view('dashboard.beta');
}

// In middleware
class FeatureGate
{
    public function handle($request, Closure $next, string $feature)
    {
        $settings = app(SettingsService::class);
        $teamId = auth()->user()->currentTeam->id;
        
        if (!$settings->get("feature.{$feature}", false, $teamId)) {
            abort(403, 'Feature not enabled');
        }
        
        return $next($request);
    }
}
```

### 6. API Configuration

```php
// Store API credentials (encrypted)
$settings->set(
    'api.stripe_secret',
    'sk_live_abc123',
    'string',
    'general',
    $teamId,
    true // encrypted
);

$settings->set(
    'api.mailgun_key',
    'key-xyz789',
    'string',
    'general',
    $teamId,
    true // encrypted
);

// Retrieve and use
$stripeKey = $settings->get('api.stripe_secret', null, $teamId);
Stripe::setApiKey($stripeKey);
```

### 7. Email Configuration

```php
// Set email settings
$settings->setMany([
    'email.from_name' => 'Acme Support',
    'email.from_address' => 'support@acme.com',
    'email.reply_to' => 'noreply@acme.com',
    'email.footer_text' => '© 2025 Acme Corporation',
], 'email', $teamId);

// Use in mail class
class InvoiceMail extends Mailable
{
    public function build(SettingsService $settings)
    {
        $teamId = $this->invoice->team_id;
        
        return $this->from(
            $settings->get('email.from_address', config('mail.from.address'), $teamId),
            $settings->get('email.from_name', config('mail.from.name'), $teamId)
        )
        ->replyTo($settings->get('email.reply_to', null, $teamId))
        ->view('emails.invoice');
    }
}
```

### 8. Notification Preferences

```php
// Set notification defaults
$settings->setMany([
    'notifications.email_enabled' => true,
    'notifications.database_enabled' => true,
    'notifications.slack_enabled' => true,
    'notifications.slack_webhook' => 'https://hooks.slack.com/...',
], 'notification', $teamId);

// Use in notification class
class OrderShipped extends Notification
{
    public function via($notifiable)
    {
        $settings = app(SettingsService::class);
        $teamId = $notifiable->currentTeam->id;
        $prefs = $settings->getNotificationDefaults($teamId);
        
        $channels = [];
        if ($prefs['email_enabled']) $channels[] = 'mail';
        if ($prefs['database_enabled']) $channels[] = 'database';
        if ($prefs['slack_enabled']) $channels[] = 'slack';
        
        return $channels;
    }
}
```

---

## Best Practices

### 1. Use Descriptive Keys

```php
// ✅ GOOD: Clear, hierarchical keys
$settings->set('company.legal_name', 'Acme Corp LLC');
$settings->set('locale.date_format', 'Y-m-d');
$settings->set('feature.beta_dashboard', true);

// ❌ BAD: Vague or flat keys
$settings->set('name', 'Acme');
$settings->set('format', 'Y-m-d');
$settings->set('beta', true);
```

### 2. Always Provide Defaults

```php
// ✅ GOOD: Fallback to config or sensible default
$timezone = $settings->get('locale.timezone', config('app.timezone'), $teamId);

// ❌ BAD: No default, could return null unexpectedly
$timezone = $settings->get('locale.timezone', null, $teamId);
```

### 3. Group Related Settings

```php
// ✅ GOOD: Organized by domain
$settings->setMany([
    'company.name' => 'Acme',
    'company.email' => 'info@acme.com',
], 'company', $teamId);

// ❌ BAD: Mixed groups
$settings->set('company.name', 'Acme', 'string', 'general');
$settings->set('company.email', 'info@acme.com', 'string', 'email');
```

### 4. Encrypt Sensitive Data

```php
// ✅ GOOD: Encrypt API keys, passwords, tokens
$settings->set('api.secret', $secret, 'string', 'general', $teamId, true);

// ❌ BAD: Storing secrets in plain text
$settings->set('api.secret', $secret, 'string', 'general', $teamId, false);
```

### 5. Use Type Hints

```php
// ✅ GOOD: Explicit types for clarity
$settings->set('feature.enabled', true, 'boolean');
$settings->set('max_items', 100, 'integer');
$settings->set('config', ['key' => 'value'], 'json');

// ⚠️ OK: Auto-inferred, but less explicit
$settings->set('feature.enabled', true); // Infers 'boolean'
```

### 6. Cache Awareness

```php
// ✅ GOOD: Clear cache after bulk updates
$settings->setMany($bulkSettings, 'company', $teamId);
$settings->clearCache(); // Ensure fresh data

// ✅ GOOD: Use domain helpers (they handle caching)
$company = $settings->getCompanyInfo($teamId);

// ❌ BAD: Manually querying settings (bypasses cache)
$name = Setting::where('key', 'company.name')->first()->getValue();
```

### 7. Team Scoping Consistency

```php
// ✅ GOOD: Always pass team ID
$teamId = auth()->user()->currentTeam->id;
$settings->get('company.name', null, $teamId);
$settings->set('company.name', 'Acme', 'string', 'company', $teamId);

// ❌ BAD: Inconsistent team scoping
$settings->get('company.name'); // Global
$settings->set('company.name', 'Acme', 'string', 'company', $teamId); // Team
```

---

## Advanced Patterns

### 1. Settings Facade

Create a facade for cleaner syntax:

```php
// app/Facades/Settings.php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\SettingsService::class;
    }
}

// Usage
use App\Facades\Settings;

$companyName = Settings::get('company.name');
Settings::set('company.name', 'Acme Corp');
```

### 2. Settings Repository Pattern

```php
// app/Repositories/CompanySettingsRepository.php
namespace App\Repositories;

use App\Services\SettingsService;

class CompanySettingsRepository
{
    public function __construct(
        private SettingsService $settings
    ) {}
    
    public function getName(int $teamId): string
    {
        return $this->settings->get('company.name', '', $teamId);
    }
    
    public function setName(string $name, int $teamId): void
    {
        $this->settings->set('company.name', $name, 'string', 'company', $teamId);
    }
    
    public function getAll(int $teamId): array
    {
        return $this->settings->getCompanyInfo($teamId);
    }
}
```

### 3. Settings Validation

```php
// app/Rules/ValidSetting.php
namespace App\Rules;

use App\Services\SettingsService;
use Illuminate\Contracts\Validation\Rule;

class ValidSetting implements Rule
{
    public function __construct(
        private string $type
    ) {}
    
    public function passes($attribute, $value)
    {
        return match ($this->type) {
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1']),
            'integer' => is_numeric($value),
            'json' => is_array($value) || $this->isValidJson($value),
            default => true,
        };
    }
    
    private function isValidJson($value): bool
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

// Usage in form request
public function rules()
{
    return [
        'value' => ['required', new ValidSetting($this->input('type'))],
    ];
}
```

### 4. Settings Observer

```php
// app/Observers/SettingObserver.php
namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SettingObserver
{
    public function updated(Setting $setting)
    {
        Log::info('Setting updated', [
            'key' => $setting->key,
            'old_value' => $setting->getOriginal('value'),
            'new_value' => $setting->value,
            'user_id' => auth()->id(),
        ]);
    }
    
    public function deleted(Setting $setting)
    {
        Log::warning('Setting deleted', [
            'key' => $setting->key,
            'user_id' => auth()->id(),
        ]);
    }
}

// Register in AppServiceProvider
Setting::observe(SettingObserver::class);
```

### 5. Settings Seeder

```php
// database/seeders/SettingsSeeder.php
namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(SettingsService $settings)
    {
        // Default company settings
        $settings->setMany([
            'company.name' => config('app.name'),
            'company.email' => 'info@example.com',
            'company.phone' => '+1 (555) 000-0000',
        ], 'company');
        
        // Default locale settings
        $settings->setMany([
            'locale.language' => 'en',
            'locale.timezone' => 'UTC',
            'locale.date_format' => 'Y-m-d',
            'locale.time_format' => 'H:i:s',
        ], 'locale');
        
        // Default currency
        $settings->set('currency.default', 'USD', 'string', 'currency');
    }
}
```

---

## Troubleshooting

### Settings Not Updating

**Problem:** Changes don't appear immediately.

**Solution:** Clear the cache:

```php
$settings->clearCache();
// Or
php artisan cache:clear
```

### Type Conversion Issues

**Problem:** Boolean stored as string "1" or "0".

**Solution:** Ensure correct type is set:

```php
// ✅ Correct
$settings->set('feature.enabled', true, 'boolean');

// ❌ Wrong
$settings->set('feature.enabled', '1', 'string');
```

### Team Scope Confusion

**Problem:** Setting not found for team.

**Solution:** Verify team ID is passed consistently:

```php
$teamId = auth()->user()->currentTeam->id;

// Check if setting exists
if (!$settings->has('company.name', $teamId)) {
    // Create it
    $settings->set('company.name', 'Default', 'string', 'company', $teamId);
}
```

### Encryption Errors

**Problem:** "The payload is invalid" when retrieving encrypted settings.

**Solution:** Ensure `APP_KEY` hasn't changed. If it has, re-encrypt:

```php
$setting = Setting::find($id);
$value = $setting->getValue(); // Decrypt with old key
$setting->is_encrypted = false;
$setting->save();

// Update APP_KEY in .env

$setting->is_encrypted = true;
$setting->setValue($value); // Re-encrypt with new key
$setting->save();
```

### Performance Issues

**Problem:** Slow queries when fetching many settings.

**Solution:** Use group queries or domain helpers:

```php
// ❌ Slow: Multiple individual queries
$name = $settings->get('company.name', null, $teamId);
$email = $settings->get('company.email', null, $teamId);
$phone = $settings->get('company.phone', null, $teamId);

// ✅ Fast: Single query
$company = $settings->getCompanyInfo($teamId);
$name = $company['name'];
$email = $company['email'];
$phone = $company['phone'];
```

---

## Related Documentation

- [Settings API Reference](./api/settings-api.md)
- [Performance Optimization](./performance-settings-optimization.md)
- [System Settings Quick Reference](./system-settings-quick-reference.md)

---

**Need Help?**

- Check the [API Reference](./api/settings-api.md) for detailed method documentation
- Review [Performance Guide](./performance-settings-optimization.md) for optimization tips
- See [Translation Guide](./TRANSLATION_GUIDE.md) for UI localization
