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
        $service = app(SettingsService::class);

        if ($key === null) {
            return $service;
        }

        return $service->get($key, $default);
    }
}

if (! function_exists('team_setting')) {
    /**
     * Get a team-specific setting.
     */
    function team_setting(string $key, mixed $default = null, ?int $teamId = null): mixed
    {
        $teamId ??= auth()->user()?->currentTeam?->id;

        return app(SettingsService::class)->get($key, $default, $teamId);
    }
}
