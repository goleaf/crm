<?php

declare(strict_types=1);

use App\Enums\ContactEmailType;
use App\Models\People;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs primary flag and legacy columns when adding emails', function () {
    /** @var People $person */
    $person = People::factory()->create([
        'primary_email' => 'primary@example.com',
        'alternate_email' => null,
    ]);

    expect($person->primary_email)->toBe('primary@example.com');
    expect($person->emails()->count())->toBe(1);

    $person->emails()->create([
        'email' => 'work@example.com',
        'type' => ContactEmailType::Work,
        'is_primary' => true,
    ]);

    $person->refresh();

    expect($person->primary_email)->toBe('work@example.com');
    expect($person->emails()->where('is_primary', true)->count())->toBe(1);
});

it('defaults the first email to primary if none are flagged', function () {
    /** @var People $person */
    $person = People::factory()->create([
        'primary_email' => null,
        'alternate_email' => null,
    ]);

    $email = $person->emails()->create([
        'email' => 'first@example.com',
        'type' => ContactEmailType::Other,
        'is_primary' => false,
    ]);

    expect($email->refresh()->is_primary)->toBeTrue();
    expect($person->refresh()->primary_email)->toBe('first@example.com');
});
