<?php

declare(strict_types=1);

use App\Models\People;
use App\Models\User;

use function Spatie\PestPluginRouteTest\routeTesting;

describe('API Routes', function (): void {
    it('requires authentication for API routes', function (): void {
        $this->getJson(route('contacts.index'))
            ->assertUnauthorized();
    });

    it('can access contact index with authentication', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        routeTesting()
            ->withToken($token)
            ->only(['contacts.index'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access contact show with authentication', function (): void {
        $user = User::factory()->create();
        $contact = People::factory()->create(['team_id' => $user->currentTeam->id]);
        $token = $user->createToken('test')->plainTextToken;

        routeTesting()
            ->withToken($token)
            ->bind('contact', $contact)
            ->only(['contacts.show'])
            ->assertAllRoutesAreAccessible();
    });

    it('returns JSON for API routes', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson(route('contacts.index'));

        expect($response)
            ->toBeOk()
            ->toHaveHeader('Content-Type', 'application/json');
    });

    it('supports precognition validation', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->withHeaders([
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'name,email',
            ])
            ->postJson(route('contacts.store'), [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        expect($response)->toHaveStatus(204);
    });
});
