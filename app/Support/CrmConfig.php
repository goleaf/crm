<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Helper class for accessing CRM configuration values.
 */
final class CrmConfig
{
    /**
     * Get a CRM configuration value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Config::get("laravel-crm.{$key}", $default);
    }

    /**
     * Check if a feature is enabled.
     */
    public static function featureEnabled(string $feature): bool
    {
        return (bool) self::get("features.{$feature}", false);
    }

    /**
     * Get the route prefix.
     */
    public static function routePrefix(): string
    {
        return self::get('routes.prefix', 'crm');
    }

    /**
     * Get the database table prefix.
     */
    public static function tablePrefix(): string
    {
        return self::get('database.table_prefix', '');
    }

    /**
     * Get the default owner ID.
     */
    public static function defaultOwnerId(): ?int
    {
        return self::get('owner.default_owner_id');
    }

    /**
     * Check if auto-assignment is enabled.
     */
    public static function autoAssignOwner(): bool
    {
        return (bool) self::get('owner.auto_assign_on_create', true);
    }

    /**
     * Get the primary color.
     */
    public static function primaryColor(): string
    {
        return self::get('ui.primary_color', 'blue');
    }

    /**
     * Get the brand name.
     */
    public static function brandName(): string
    {
        return self::get('ui.brand_name', config('app.name'));
    }

    /**
     * Get the logo URL.
     */
    public static function logoUrl(): ?string
    {
        return self::get('ui.logo_url');
    }

    /**
     * Get items per page.
     */
    public static function itemsPerPage(): int
    {
        return (int) self::get('ui.items_per_page', 25);
    }

    /**
     * Get the date format.
     */
    public static function dateFormat(): string
    {
        return self::get('ui.date_format', 'Y-m-d');
    }

    /**
     * Get the time format.
     */
    public static function timeFormat(): string
    {
        return self::get('ui.time_format', 'H:i:s');
    }

    /**
     * Get the datetime format.
     */
    public static function datetimeFormat(): string
    {
        return self::get('ui.datetime_format', 'Y-m-d H:i:s');
    }

    /**
     * Check if dark mode is enabled.
     */
    public static function darkModeEnabled(): bool
    {
        return (bool) self::get('ui.enable_dark_mode', true);
    }

    /**
     * Get the default theme mode.
     */
    public static function defaultThemeMode(): string
    {
        return self::get('ui.default_theme_mode', 'light');
    }

    /**
     * Check if an integration is enabled.
     */
    public static function integrationEnabled(string $integration): bool
    {
        return (bool) self::get("integrations.{$integration}.enabled", false);
    }

    /**
     * Get email notification settings.
     */
    public static function emailNotificationEnabled(string $type): bool
    {
        return (bool) self::get("email.notifications.{$type}", false);
    }

    /**
     * Get the default currency.
     */
    public static function defaultCurrency(): string
    {
        return self::get('currency.default', 'USD');
    }

    /**
     * Get the currency symbol.
     */
    public static function currencySymbol(): string
    {
        return self::get('currency.symbol', '$');
    }

    /**
     * Get business hours for a specific day.
     */
    public static function businessHours(string $day): ?array
    {
        return self::get("business_hours.{$day}");
    }

    /**
     * Check if a day is a business day.
     */
    public static function isBusinessDay(string $day): bool
    {
        return self::businessHours($day) !== null;
    }

    /**
     * Get the fiscal year start month.
     */
    public static function fiscalYearStartMonth(): int
    {
        return (int) self::get('fiscal_year.start_month', 1);
    }

    /**
     * Get the fiscal year start day.
     */
    public static function fiscalYearStartDay(): int
    {
        return (int) self::get('fiscal_year.start_day', 1);
    }

    /**
     * Get task default settings.
     */
    public static function taskDefaults(): array
    {
        return [
            'priority' => self::get('tasks.default_priority', 'medium'),
            'status' => self::get('tasks.default_status', 'pending'),
            'auto_assign' => self::get('tasks.auto_assign_to_creator', true),
            'reminder_hours' => self::get('tasks.reminder_before_due', 24),
        ];
    }

    /**
     * Get opportunity default settings.
     */
    public static function opportunityDefaults(): array
    {
        return [
            'stage' => self::get('opportunities.default_stage', 'qualification'),
            'probability' => self::get('opportunities.default_probability', 10),
            'auto_calculate_probability' => self::get('opportunities.auto_calculate_probability', true),
            'require_close_reason' => self::get('opportunities.require_close_reason', true),
        ];
    }

    /**
     * Get lead default settings.
     */
    public static function leadDefaults(): array
    {
        return [
            'status' => self::get('leads.default_status', 'new'),
            'source' => self::get('leads.default_source', 'website'),
            'auto_assign' => self::get('leads.auto_assign_enabled', false),
            'auto_assign_method' => self::get('leads.auto_assign_method', 'round_robin'),
        ];
    }

    /**
     * Get support case default settings.
     */
    public static function supportCaseDefaults(): array
    {
        return [
            'priority' => self::get('support_cases.default_priority', 'medium'),
            'status' => self::get('support_cases.default_status', 'open'),
            'auto_assign' => self::get('support_cases.auto_assign_enabled', false),
            'sla_enabled' => self::get('support_cases.sla_enabled', true),
        ];
    }

    /**
     * Get SLA response time for a priority level.
     */
    public static function slaResponseTime(string $priority): int
    {
        return (int) self::get("support_cases.sla_response_time.{$priority}", 24);
    }

    /**
     * Get SLA resolution time for a priority level.
     */
    public static function slaResolutionTime(string $priority): int
    {
        return (int) self::get("support_cases.sla_resolution_time.{$priority}", 72);
    }

    /**
     * Get allowed file extensions for a type.
     */
    public static function allowedFileExtensions(string $type = 'documents'): array
    {
        return self::get("uploads.allowed_extensions.{$type}", []);
    }

    /**
     * Get max file upload size in KB.
     */
    public static function maxFileSize(): int
    {
        return (int) self::get('uploads.max_file_size', 10240);
    }

    /**
     * Check if AI features are enabled.
     */
    public static function aiEnabled(): bool
    {
        return (bool) self::get('ai.enabled', false);
    }

    /**
     * Get AI provider.
     */
    public static function aiProvider(): string
    {
        return self::get('ai.provider', 'anthropic');
    }

    /**
     * Check if audit logging is enabled.
     */
    public static function auditLogEnabled(): bool
    {
        return (bool) self::get('security.audit_log_enabled', true);
    }

    /**
     * Check if 2FA is required.
     */
    public static function require2FA(): bool
    {
        return (bool) self::get('security.require_2fa', false);
    }

    /**
     * Get session timeout in minutes.
     */
    public static function sessionTimeout(): int
    {
        return (int) self::get('security.session_timeout', 120);
    }

    /**
     * Get cache TTL in seconds.
     */
    public static function cacheTTL(): int
    {
        return (int) self::get('cache.ttl', 3600);
    }

    /**
     * Check if caching is enabled.
     */
    public static function cacheEnabled(): bool
    {
        return (bool) self::get('cache.enabled', true);
    }

    /**
     * Get all configuration as an array.
     */
    public static function all(): array
    {
        return Config::get('laravel-crm', []);
    }
}
