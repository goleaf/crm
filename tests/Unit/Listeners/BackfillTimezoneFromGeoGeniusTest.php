<?php

declare(strict_types=1);

use App\Listeners\Auth\BackfillTimezoneFromGeoGenius;
use App\Models\User;
use Devrabiul\LaravelGeoGenius\LaravelGeoGenius;
use Illuminate\Auth\Events\Login;

afterEach(function (): void {
    Mockery::close();
});

it('persists detected timezone when the user is missing one', function (): void {
    $timezoneService = new class
    {
        public function getUserTimezone(): string
        {
            return 'Europe/Berlin';
        }
    };

    $geo = Mockery::mock(LaravelGeoGenius::class);
    $geo->shouldReceive('timezone')->andReturn($timezoneService);

    $user = Mockery::mock(User::class)->makePartial();
    $user->timezone = null;
    $user->shouldReceive('forceFill')->once()->with(['timezone' => 'Europe/Berlin'])->andReturnSelf();
    $user->shouldReceive('save')->once();

    $listener = new BackfillTimezoneFromGeoGenius($geo);
    $listener->handle(new Login('web', $user, false));
});

it('does not override an existing timezone', function (): void {
    $timezoneService = new class
    {
        public function getUserTimezone(): string
        {
            return 'Europe/Berlin';
        }
    };

    $geo = Mockery::mock(LaravelGeoGenius::class);
    $geo->shouldReceive('timezone')->andReturn($timezoneService);

    $user = Mockery::mock(User::class)->makePartial();
    $user->timezone = 'America/New_York';
    $user->shouldNotReceive('forceFill');
    $user->shouldNotReceive('save');

    $listener = new BackfillTimezoneFromGeoGenius($geo);
    $listener->handle(new Login('web', $user, false));
});
