<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Team;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(SettingsService::class);
});

it('persists settings to database', function (): void {
    $this->service->set('persist.key', 'persist value', 'string', 'general');

    $this->assertDatabaseHas('settings', [
        'key' => 'persist.key',
        'value' => 'persist value',
        'type' => 'string',
        'group' => 'general',
    ]);
});

it('retrieves settings from database', function (): void {
    Setting::create([
        'key' => 'db.key',
        'value' => 'db value',
        'type' => 'string',
        'group' => 'general',
    ]);

    expect($this->service->get('db.key'))->toBe('db value');
});

it('handles concurrent updates correctly', function (): void {
    $this->service->set('concurrent.key', 'initial', 'string', 'general');

    // Simulate concurrent update
    $this->service->set('concurrent.key', 'update1', 'string', 'general');
    $this->service->set('concurrent.key', 'update2', 'string', 'general');

    expect($this->service->get('concurrent.key'))->toBe('update2')
        ->and(Setting::where('key', 'concurrent.key')->count())->toBe(1);
});

it('maintains data integrity across transactions', function (): void {
    \DB::transaction(function (): void {
        $this->service->set('transaction.key1', 'value1', 'string', 'general');
        $this->service->set('transaction.key2', 'value2', 'string', 'general');
    });

    expect($this->service->get('transaction.key1'))->toBe('value1')
        ->and($this->service->get('transaction.key2'))->toBe('value2');
});

it('rolls back on transaction failure', function (): void {
    try {
        \DB::transaction(function (): void {
            $this->service->set('rollback.key', 'value', 'string', 'general');
            throw new \Exception('Force rollback');
        });
    } catch (\Exception $e) {
        // Expected
    }

    expect($this->service->has('rollback.key'))->toBeFalse();
});

it('handles team isolation correctly', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $this->service->set('isolated.key', 'team1 value', 'string', 'general', $team1->id);
    $this->service->set('isolated.key', 'team2 value', 'string', 'general', $team2->id);

    expect($this->service->get('isolated.key', null, $team1->id))->toBe('team1 value')
        ->and($this->service->get('isolated.key', null, $team2->id))->toBe('team2 value');
});

it('cleans up team settings when team is deleted', function (): void {
    $team = Team::factory()->create();

    $this->service->set('team.setting1', 'value1', 'string', 'general', $team->id);
    $this->service->set('team.setting2', 'value2', 'string', 'general', $team->id);

    $team->delete();

    expect(Setting::where('team_id', $team->id)->count())->toBe(0);
});

it('caches settings for performance', function (): void {
    $this->service->set('cached.key', 'cached value', 'string', 'general');

    // Clear query log
    \DB::enableQueryLog();
    \DB::flushQueryLog();

    // First call
    $this->service->get('cached.key');
    $firstCallQueries = count(\DB::getQueryLog());

    \DB::flushQueryLog();

    // Second call should use cache
    $this->service->get('cached.key');
    $secondCallQueries = count(\DB::getQueryLog());

    expect($secondCallQueries)->toBeLessThan($firstCallQueries);
});

it('invalidates cache on update', function (): void {
    $this->service->set('cache.invalidate', 'initial', 'string', 'general');
    $this->service->get('cache.invalidate'); // Cache it

    $this->service->set('cache.invalidate', 'updated', 'string', 'general');

    // Should get updated value, not cached
    expect($this->service->get('cache.invalidate'))->toBe('updated');
});

it('invalidates cache on delete', function (): void {
    $this->service->set('cache.delete', 'value', 'string', 'general');
    $this->service->get('cache.delete'); // Cache it

    $this->service->delete('cache.delete');

    expect($this->service->get('cache.delete', 'default'))->toBe('default');
});

it('handles large values correctly', function (): void {
    $largeValue = str_repeat('a', 10000);

    $this->service->set('large.value', $largeValue, 'string', 'general');

    expect($this->service->get('large.value'))->toBe($largeValue);
});

it('handles complex nested arrays', function (): void {
    $complexData = [
        'level1' => [
            'level2' => [
                'level3' => [
                    'key' => 'value',
                    'number' => 42,
                    'boolean' => true,
                ],
            ],
        ],
    ];

    $this->service->set('complex.data', $complexData, 'array', 'general');

    expect($this->service->get('complex.data'))->toBe($complexData);
});

it('handles special characters in values', function (): void {
    $specialValue = "Test with 'quotes', \"double quotes\", and \n newlines";

    $this->service->set('special.chars', $specialValue, 'string', 'general');

    expect($this->service->get('special.chars'))->toBe($specialValue);
});

it('handles unicode characters', function (): void {
    $unicodeValue = '测试 тест テスト 🚀';

    $this->service->set('unicode.value', $unicodeValue, 'string', 'general');

    expect($this->service->get('unicode.value'))->toBe($unicodeValue);
});

it('handles null values correctly', function (): void {
    $this->service->set('null.value', null, 'string', 'general');

    expect($this->service->get('null.value'))->toBeNull();
});

it('handles empty string values', function (): void {
    $this->service->set('empty.string', '', 'string', 'general');

    expect($this->service->get('empty.string'))->toBe('');
});

it('handles zero values correctly', function (): void {
    $this->service->set('zero.int', 0, 'integer', 'general');
    $this->service->set('zero.float', 0.0, 'float', 'general');

    expect($this->service->get('zero.int'))->toBe(0)
        ->and($this->service->get('zero.float'))->toBe(0.0);
});

it('handles false boolean correctly', function (): void {
    $this->service->set('false.bool', false, 'boolean', 'general');

    expect($this->service->get('false.bool'))->toBeFalse();
});

it('handles empty arrays correctly', function (): void {
    $this->service->set('empty.array', [], 'array', 'general');

    expect($this->service->get('empty.array'))->toBe([]);
});

it('supports batch operations efficiently', function (): void {
    $settings = [];
    for ($i = 0; $i < 100; $i++) {
        $settings["batch.key{$i}"] = "value{$i}";
    }

    $this->service->setMany($settings, 'batch');

    $retrieved = $this->service->getGroup('batch');

    expect($retrieved)->toHaveCount(100);
});

it('maintains consistency under high load', function (): void {
    $iterations = 50;

    for ($i = 0; $i < $iterations; $i++) {
        $this->service->set("load.key{$i}", "value{$i}", 'string', 'general');
    }

    for ($i = 0; $i < $iterations; $i++) {
        expect($this->service->get("load.key{$i}"))->toBe("value{$i}");
    }
});

it('handles encrypted settings end-to-end', function (): void {
    $secretValue = 'super secret password';

    $this->service->set('secret.password', $secretValue, 'string', 'general', null, true);

    // Value should be encrypted in database
    $dbValue = Setting::where('key', 'secret.password')->value('value');
    expect($dbValue)->not->toBe($secretValue);

    // But service should decrypt it
    expect($this->service->get('secret.password'))->toBe($secretValue);
});

it('provides correct company info with defaults', function (): void {
    $companyInfo = $this->service->getCompanyInfo();

    expect($companyInfo)->toBeArray()
        ->and($companyInfo)->toHaveKeys(['name', 'legal_name', 'tax_id', 'address', 'phone', 'email', 'website', 'logo_url']);
});

it('provides correct locale settings with defaults', function (): void {
    $localeSettings = $this->service->getLocaleSettings();

    expect($localeSettings)->toBeArray()
        ->and($localeSettings)->toHaveKeys(['locale', 'timezone', 'date_format', 'time_format', 'first_day_of_week']);
});

it('provides correct currency settings with defaults', function (): void {
    $currencySettings = $this->service->getCurrencySettings();

    expect($currencySettings)->toBeArray()
        ->and($currencySettings)->toHaveKeys(['default_currency', 'exchange_rates', 'auto_update_rates']);
});

it('provides correct fiscal year settings with defaults', function (): void {
    $fiscalSettings = $this->service->getFiscalYearSettings();

    expect($fiscalSettings)->toBeArray()
        ->and($fiscalSettings)->toHaveKeys(['start_month', 'start_day']);
});

it('provides correct business hours with defaults', function (): void {
    $businessHours = $this->service->getBusinessHours();

    expect($businessHours)->toBeArray()
        ->and($businessHours)->toHaveKeys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
});

it('provides correct notification defaults', function (): void {
    $notificationDefaults = $this->service->getNotificationDefaults();

    expect($notificationDefaults)->toBeArray()
        ->and($notificationDefaults)->toHaveKeys(['email_enabled', 'database_enabled', 'slack_enabled', 'slack_webhook']);
});
