<?php

declare(strict_types=1);

use App\Services\SettingsService;

if (! function_exists('setting')) {
    /**
     * Get or set a system setting.
     *
     * @return mixed|\App\Services\SettingsService
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $service = resolve(SettingsService::class);

        if ($key === null) {
            return $service;
        }

        return $service->get($key, $default);
    }
}

if (! function_exists('brand_name')) {
    /**
     * Get the current brand name, preferring CRM config over app name.
     */
    function brand_name(): string
    {
        return (string) config('laravel-crm.ui.brand_name', config('app.name', 'CRM'));
    }
}

if (! function_exists('team_setting')) {
    /**
     * Get a team-specific setting.
     */
    function team_setting(string $key, mixed $default = null, ?int $teamId = null): mixed
    {
        $teamId ??= auth()->user()?->currentTeam?->id;

        return resolve(SettingsService::class)->get($key, $default, $teamId);
    }
}

if (! function_exists('brand_social_url')) {
    function brand_social_url(string $key, ?string $default = null): ?string
    {
        return config("laravel-crm.ui.social.{$key}", $default);
    }
}

if (! function_exists('brand_logo_asset')) {
    /**
     * Get the logo asset path.
     */
    function brand_logo_asset(): string
    {
        return asset(config('laravel-crm.ui.logo_asset', 'crm-logo.svg'));
    }
}

if (! function_exists('brand_logomark_asset')) {
    /**
     * Get the logomark asset path.
     */
    function brand_logomark_asset(): string
    {
        return asset(config('laravel-crm.ui.logomark_asset', 'crm-logomark.svg'));
    }
}

if (! function_exists('brand_logo_white_asset')) {
    /**
     * Get the white logo asset path.
     */
    function brand_logo_white_asset(): string
    {
        return asset(config('laravel-crm.ui.logo_white_asset', 'images/crm-logo-white.png'));
    }
}

if (! function_exists('brand_command_prefix')) {
    /**
     * Get a slugified command prefix based on the brand name.
     * Used for artisan command names like {prefix}:install
     */
    function brand_command_prefix(): string
    {
        $prefix = config('laravel-crm.ui.command_prefix');

        // If not explicitly set, generate from brand name
        if ($prefix === null) {
            $name = brand_name();
            $slug = strtolower((string) preg_replace('/[^a-z0-9]+/', '-', $name));
            $prefix = trim($slug, '-') ?: 'crm';
        }

        return (string) $prefix;
    }
}

// Compat: older Filament packages expecting Filament\Forms\Form.
if (! class_exists(\Filament\Forms\Form::class) && class_exists(\Filament\Schemas\Schema::class)) {
    class_alias(\Filament\Schemas\Schema::class, \Filament\Forms\Form::class);
}
