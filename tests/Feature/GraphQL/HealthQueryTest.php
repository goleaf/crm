<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('health query returns app metadata', function (): void {
    $user = User::factory()->create();

    $query = <<<'GRAPHQL'
    {
        health {
            name
            environment
            frameworkVersion
            phpVersion
            timestamp
        }
    }
    GRAPHQL;

    $response = actingAs($user, 'sanctum')->postJson('/graphql', [
        'query' => $query,
    ]);

    $response->assertOk();

    $data = $response->json('data.health');

    expect($data)->toMatchArray([
        'name' => config('app.name'),
        'environment' => app()->environment(),
    ]);
    expect($data['frameworkVersion'])->not->toBeEmpty()
        ->and($data['phpVersion'])->not->toBeEmpty()
        ->and($data['timestamp'])->not->toBeEmpty();
});

test('me query returns the authenticated user', function (): void {
    $user = User::factory()->create();

    $query = <<<'GRAPHQL'
    {
        me {
            id
            name
            email
        }
    }
    GRAPHQL;

    $response = actingAs($user, 'sanctum')->postJson('/graphql', [
        'query' => $query,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.me.id', (string) $user->getKey());
    $response->assertJsonPath('data.me.name', $user->name);
    $response->assertJsonPath('data.me.email', $user->email);
});
