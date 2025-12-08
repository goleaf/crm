<?php

declare(strict_types=1);

use App\Models\People;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('people factory creates valid person with all required fields', function (): void {
    $person = People::factory()->create();

    expect($person)->toBeInstanceOf(People::class)
        ->and($person->name)->not->toBeNull()
        ->and($person->team_id)->not->toBeNull()
        ->and($person->primary_email)->not->toBeNull();
});

test('people factory generates valid address with postal code', function (): void {
    $person = People::factory()->create();

    expect($person->address_postal_code)->not->toBeNull()
        ->and($person->address_postal_code)->toBeString()
        ->and($person->address_street)->not->toBeNull()
        ->and($person->address_city)->not->toBeNull()
        ->and($person->address_state)->not->toBeNull()
        ->and($person->address_country)->not->toBeNull();
});

test('people factory generates valid postal code format', function (): void {
    $person = People::factory()->create();

    // Postal code should be a 5-digit string
    expect($person->address_postal_code)->toBeString()
        ->and(strlen($person->address_postal_code))->toBe(5)
        ->and((int) $person->address_postal_code)->toBeGreaterThanOrEqual(10000)
        ->and((int) $person->address_postal_code)->toBeLessThanOrEqual(99999);
});

test('people factory generates valid social links', function (): void {
    $person = People::factory()->create();

    expect($person->social_links)->toBeArray()
        ->and($person->social_links)->toHaveKey('linkedin');
});

test('people factory creates associated team', function (): void {
    $person = People::factory()->create();

    expect($person->team)->toBeInstanceOf(Team::class);
});

test('people factory can override default values', function (): void {
    $customName = 'John Doe';
    $customEmail = 'john.doe@example.com';

    $person = People::factory()->create([
        'name' => $customName,
        'primary_email' => $customEmail,
    ]);

    expect($person->name)->toBe($customName)
        ->and($person->primary_email)->toBe($customEmail);
});

test('people factory generates valid email addresses', function (): void {
    $person = People::factory()->create();

    expect($person->primary_email)->toBeString()
        ->and(filter_var($person->primary_email, FILTER_VALIDATE_EMAIL))->not->toBeFalse()
        ->and($person->alternate_email)->toBeString()
        ->and(filter_var($person->alternate_email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
});

test('people factory generates valid phone numbers', function (): void {
    $person = People::factory()->create();

    expect($person->phone_mobile)->toBeString()
        ->and($person->phone_mobile)->toStartWith('+');
});

test('people factory generates valid job information', function (): void {
    $person = People::factory()->create();

    expect($person->job_title)->toBeString()
        ->and($person->department)->toBeString()
        ->and($person->role)->toBeString();
});

test('people factory generates valid segments array', function (): void {
    $person = People::factory()->create();

    expect($person->segments)->toBeArray()
        ->and($person->segments)->not->toBeEmpty();
});

test('people factory can create multiple people', function (): void {
    $people = People::factory()->count(5)->create();

    expect($people)->toHaveCount(5)
        ->and(People::count())->toBe(5);
});

test('people factory generates unique timestamps for multiple records', function (): void {
    $people = People::factory()->count(3)->create();

    $timestamps = $people->pluck('created_at')->unique();

    expect($timestamps)->toHaveCount(3);
});
