<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Models\User;
use DateTimeZone;
use Devrabiul\LaravelGeoGenius\LaravelGeoGenius;
use Illuminate\Auth\Events\Login;
use Throwable;

final readonly class BackfillTimezoneFromGeoGenius
{
    public function __construct(private LaravelGeoGenius $geoGenius) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->timezone !== null) {
            return;
        }

        $timezone = $this->geoGenius->timezone()->getUserTimezone();

        if ($this->isValidTimezone($timezone)) {
            $user->forceFill(['timezone' => $timezone])->save();
        }
    }

    private function isValidTimezone(?string $timezone): bool
    {
        if (! is_string($timezone) || $timezone === '') {
            return false;
        }

        try {
            new DateTimeZone($timezone);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
