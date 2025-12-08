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
