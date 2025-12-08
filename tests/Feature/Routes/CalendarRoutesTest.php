<?php

declare(strict_types=1);

use App\Models\User;

use function Spatie\PestPluginRouteTest\routeTesting;

describe('Calendar Routes', function (): void {
    it('can access calendar index', function (): void {
        $user = User::factory()->create();

        routeTesting()
            ->actingAs($user)
            ->only(['calendar'])
            ->assertAllRoutesAreAccessible();
    });

    it('can export calendar as iCal', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('calendar.export.ical'));

        expect($response)
            ->toBeOk()
            ->toHaveHeader('Content-Type', 'text/calendar; charset=UTF-8');
    });

    it('can create calendar events', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('calendar.store'), [
                'title' => 'Test Event',
                'start' => now()->toIso8601String(),
                'end' => now()->addHour()->toIso8601String(),
            ]);

        expect($response)->toBeSuccessful();
    });

    it('validates calendar event creation', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('calendar.store'), [
                'title' => '', // Invalid
            ]);

        expect($response)->toHaveStatus(422);
    });
});
