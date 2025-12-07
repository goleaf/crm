<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing system settings with caching and type safety.
 */
final class SettingsService
{
    private const string CACHE_PREFIX = 'settings:';

    private const int CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null, ?int $teamId = null): mixed
    {
        $cacheKey = $this->getCacheKey($key, $teamId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default, $teamId) {
            $setting = Setting::where('key', $key)
                ->when($teamId, fn ($query) => $query->where('team_id', $teamId))
                ->first();

            return $setting ? $setting->getValue() : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general',
        ?int $teamId = null,
        bool $isEncrypted = false
    ): Setting {
        $setting = Setting::updateOrCreate(
            ['key' => $key, 'team_id' => $teamId],
            [
                'type' => $type,
                'group' => $group,
                'is_encrypted' => $isEncrypted,
            ]
        );

        $setting->setValue($value);
        $setting->save();

        $this->clearCache($key, $teamId);

        return $setting;
    }

    /**
     * Get all settings in a group.
     */
    public function getGroup(string $group, ?int $teamId = null): Collection
    {
        return Setting::where('group', $group)
            ->when($teamId, fn ($query) => $query->where('team_id', $teamId))
            ->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->getValue()]);
    }

    /**
     * Set multiple settings at once.
     */
    public function setMany(array $settings, string $group = 'general', ?int $teamId = null): void
    {
        foreach ($settings as $key => $value) {
            $type = $this->inferType($value);
            $this->set($key, $value, $type, $group, $teamId);
        }
    }

    /**
     * Delete a setting.
     */
    public function delete(string $key, ?int $teamId = null): bool
    {
        $deleted = Setting::where('key', $key)
            ->when($teamId, fn ($query) => $query->where('team_id', $teamId))
            ->delete();

        if ($deleted) {
            $this->clearCache($key, $teamId);
        }

        return (bool) $deleted;
    }

    /**
     * Check if a setting exists.
     */
    public function has(string $key, ?int $teamId = null): bool
    {
        return Setting::where('key', $key)
            ->when($teamId, fn ($query) => $query->where('team_id', $teamId))
            ->exists();
    }

    /**
     * Clear cache for a specific setting.
     */
    public function clearCache(?string $key = null, ?int $teamId = null): void
    {
        if ($key) {
            Cache::forget($this->getCacheKey($key, $teamId));
        } else {
            Cache::flush();
        }
    }

    /**
     * Get cache key for a setting.
     */
    private function getCacheKey(string $key, ?int $teamId): string
    {
        return self::CACHE_PREFIX.($teamId ? "team:{$teamId}:" : 'global:').$key;
    }

    /**
     * Infer the type of a value.
     */
    private function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            default => 'string',
        };
    }

    /**
     * Get company information settings.
     */
    public function getCompanyInfo(?int $teamId = null): array
    {
        return [
            'name' => $this->get('company.name', config('app.name'), $teamId),
            'legal_name' => $this->get('company.legal_name', null, $teamId),
            'tax_id' => $this->get('company.tax_id', null, $teamId),
            'address' => $this->get('company.address', null, $teamId),
            'phone' => $this->get('company.phone', null, $teamId),
            'email' => $this->get('company.email', null, $teamId),
            'website' => $this->get('company.website', null, $teamId),
            'logo_url' => $this->get('company.logo_url', null, $teamId),
        ];
    }

    /**
     * Get locale settings.
     */
    public function getLocaleSettings(?int $teamId = null): array
    {
        return [
            'locale' => $this->get('locale.language', config('app.locale'), $teamId),
            'timezone' => $this->get('locale.timezone', config('app.timezone'), $teamId),
            'date_format' => $this->get('locale.date_format', 'Y-m-d', $teamId),
            'time_format' => $this->get('locale.time_format', 'H:i:s', $teamId),
            'first_day_of_week' => $this->get('locale.first_day_of_week', 0, $teamId),
        ];
    }

    /**
     * Get currency settings.
     */
    public function getCurrencySettings(?int $teamId = null): array
    {
        return [
            'default_currency' => $this->get('currency.default', config('company.default_currency', 'USD'), $teamId),
            'exchange_rates' => $this->get('currency.exchange_rates', [], $teamId),
            'auto_update_rates' => $this->get('currency.auto_update_rates', false, $teamId),
        ];
    }

    /**
     * Get fiscal year settings.
     */
    public function getFiscalYearSettings(?int $teamId = null): array
    {
        return [
            'start_month' => $this->get('fiscal.start_month', 1, $teamId),
            'start_day' => $this->get('fiscal.start_day', 1, $teamId),
        ];
    }

    /**
     * Get business hours settings.
     */
    public function getBusinessHours(?int $teamId = null): array
    {
        return [
            'monday' => $this->get('business_hours.monday', ['start' => '09:00', 'end' => '17:00'], $teamId),
            'tuesday' => $this->get('business_hours.tuesday', ['start' => '09:00', 'end' => '17:00'], $teamId),
            'wednesday' => $this->get('business_hours.wednesday', ['start' => '09:00', 'end' => '17:00'], $teamId),
            'thursday' => $this->get('business_hours.thursday', ['start' => '09:00', 'end' => '17:00'], $teamId),
            'friday' => $this->get('business_hours.friday', ['start' => '09:00', 'end' => '17:00'], $teamId),
            'saturday' => $this->get('business_hours.saturday', null, $teamId),
            'sunday' => $this->get('business_hours.sunday', null, $teamId),
        ];
    }

    /**
     * Get notification defaults.
     */
    public function getNotificationDefaults(?int $teamId = null): array
    {
        return [
            'email_enabled' => $this->get('notifications.email_enabled', true, $teamId),
            'database_enabled' => $this->get('notifications.database_enabled', true, $teamId),
            'slack_enabled' => $this->get('notifications.slack_enabled', false, $teamId),
            'slack_webhook' => $this->get('notifications.slack_webhook', null, $teamId),
        ];
    }
}
