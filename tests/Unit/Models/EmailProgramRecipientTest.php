<?php

declare(strict_types=1);

use App\Models\EmailProgram;
use App\Models\EmailProgramRecipient;
use HosmelQ\NameOfPerson\PersonName;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('email program recipient casts name to person name', function (): void {
    $program = EmailProgram::factory()->create();

    $recipient = new EmailProgramRecipient([
        'email_program_id' => $program->id,
        'email' => 'ada@example.com',
        'name' => new PersonName('Ada', 'Lovelace'),
    ]);

    $recipient->save();

    expect($recipient->first_name)->toBe('Ada')
        ->and($recipient->last_name)->toBe('Lovelace')
        ->and($recipient->name)->toBeInstanceOf(PersonName::class)
        ->and($recipient->full_name)->toBe('Ada Lovelace');
});
