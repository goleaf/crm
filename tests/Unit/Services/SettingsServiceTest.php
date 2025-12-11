<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Team;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = resolve(SettingsService::class);
});

it('can set and get a string setting', function (): void {
    $this->service->set('test.key', 'test value', 'string', 'general');

    expect($this->service->get('test.key'))->toBe('test value');
});

it('can set and get an integer setting', function (): void {
    $this->service->set('test.number', 42, 'integer', 'general');

    expect($this->service->get('test.number'))->toBe(42);
});

it('can set and get a boolean setting', function (): void {
    $this->service->set('test.flag', true, 'boolean', 'general');

    expect($this->service->get('test.flag'))->toBeTrue();
});

it('can set and get an array setting', function (): void {
    $data = ['key1' => 'value1', 'key2' => 'value2'];
    $this->service->set('test.array', $data, 'array', 'general');

    expect($this->service->get('test.array'))->toBe($data);
});

it('returns default value when setting does not exist', function (): void {
    expect($this->service->get('nonexistent.key', 'default'))->toBe('default');
});

it('can update an existing setting', function (): void {
    $this->service->set('test.key', 'initial', 'string', 'general');
    $this->service->set('test.key', 'updated', 'string', 'general');

    expect($this->service->get('test.key'))->toBe('updated');
    expect(Setting::where('key', 'test.key')->count())->toBe(1);
});

it('can delete a setting', function (): void {
    $this->service->set('test.key', 'value', 'string', 'general');

    expect($this->service->has('test.key'))->toBeTrue();

    $this->service->delete('test.key');

    expect($this->service->has('test.key'))->toBeFalse();
});

it('can get all settings in a group', function (): void {
    $this->service->set('group1.key1', 'value1', 'string', 'group1');
    $this->service->set('group1.key2', 'value2', 'string', 'group1');
    $this->service->set('group2.key3', 'value3', 'string', 'group2');

    $group1Settings = $this->service->getGroup('group1');

    expect($group1Settings)->toHaveCount(2)
        ->and($group1Settings->get('group1.key1'))->toBe('value1')
        ->and($group1Settings->get('group1.key2'))->toBe('value2');
});

it('can set multiple settings at once', function (): void {
    $settings = [
        'multi.key1' => 'value1',
        'multi.key2' => 42,
        'multi.key3' => true,
    ];

    $this->service->setMany($settings, 'multi');

    expect($this->service->get('multi.key1'))->toBe('value1')
        ->and($this->service->get('multi.key2'))->toBe(42)
        ->and($this->service->get('multi.key3'))->toBeTrue();
});

it('caches settings for performance', function (): void {
    $this->service->set('cached.key', 'cached value', 'string', 'general');

    // First call should hit database
    $value1 = $this->service->get('cached.key');

    // Second call should hit cache
    $value2 = $this->service->get('cached.key');

    expect($value1)->toBe('cached value')
        ->and($value2)->toBe('cached value');
});

it('clears cache when setting is updated', function (): void {
    $this->service->set('cache.key', 'initial', 'string', 'general');
    $this->service->get('cache.key'); // Cache it

    $this->service->set('cache.key', 'updated', 'string', 'general');

    expect($this->service->get('cache.key'))->toBe('updated');
});

it('supports team-specific settings', function (): void {
    $team = Team::factory()->create();

    $this->service->set('team.key', 'team value', 'string', 'general', $team->id);
    $this->service->set('team.key', 'global value', 'string', 'general', null);

    expect($this->service->get('team.key', null, $team->id))->toBe('team value')
        ->and($this->service->get('team.key', null, null))->toBe('global value');
});

it('can handle encrypted settings', function (): void {
    $setting = $this->service->set('secret.key', 'secret value', 'string', 'general', null, true);

    expect($setting->is_encrypted)->toBeTrue()
        ->and($setting->value)->not->toBe('secret value') // Should be encrypted in DB
        ->and($setting->getValue())->toBe('secret value'); // Should decrypt when retrieved
});

it('provides company info settings', function (): void {
    $this->service->set('company.name', 'Test Company', 'string', 'company');
    $this->service->set('company.email', 'test@example.com', 'string', 'company');

    $companyInfo = $this->service->getCompanyInfo();

    expect($companyInfo)->toHaveKey('name')
        ->and($companyInfo['name'])->toBe('Test Company')
        ->and($companyInfo['email'])->toBe('test@example.com');
});

it('provides locale settings', function (): void {
    $this->service->set('locale.language', 'en', 'string', 'locale');
    $this->service->set('locale.timezone', 'America/New_York', 'string', 'locale');

    $localeSettings = $this->service->getLocaleSettings();

    expect($localeSettings)->toHaveKey('locale')
        ->and($localeSettings['locale'])->toBe('en')
        ->and($localeSettings['timezone'])->toBe('America/New_York');
});

it('provides currency settings', function (): void {
    $rates = ['EUR' => 0.85, 'GBP' => 0.73];
    $this->service->set('currency.default', 'USD', 'string', 'currency');
    $this->service->set('currency.exchange_rates', $rates, 'array', 'currency');

    $currencySettings = $this->service->getCurrencySettings();

    expect($currencySettings)->toHaveKey('default_currency')
        ->and($currencySettings['default_currency'])->toBe('USD')
        ->and($currencySettings['exchange_rates'])->toBe($rates);
});

it('provides fiscal year settings', function (): void {
    $this->service->set('fiscal.start_month', 7, 'integer', 'fiscal');
    $this->service->set('fiscal.start_day', 1, 'integer', 'fiscal');

    $fiscalSettings = $this->service->getFiscalYearSettings();

    expect($fiscalSettings)->toHaveKey('start_month')
        ->and($fiscalSettings['start_month'])->toBe(7)
        ->and($fiscalSettings['start_day'])->toBe(1);
});

it('provides business hours settings', function (): void {
    $hours = ['start' => '09:00', 'end' => '17:00'];
    $this->service->set('business_hours.monday', $hours, 'array', 'business_hours');

    $businessHours = $this->service->getBusinessHours();

    expect($businessHours)->toHaveKey('monday')
        ->and($businessHours['monday'])->toBe($hours);
});

it('provides notification defaults', function (): void {
    $this->service->set('notifications.email_enabled', true, 'boolean', 'notification');
    $this->service->set('notifications.slack_enabled', false, 'boolean', 'notification');

    $notificationDefaults = $this->service->getNotificationDefaults();

    expect($notificationDefaults)->toHaveKey('email_enabled')
        ->and($notificationDefaults['email_enabled'])->toBeTrue()
        ->and($notificationDefaults['slack_enabled'])->toBeFalse();
});
