<?php

declare(strict_types=1);

use App\Support\PersonNameFormatter;
use HosmelQ\NameOfPerson\PersonName;

it('parses and formats names with name-of-person', function (): void {
    $name = PersonNameFormatter::make('Ada Lovelace');

    expect($name)->toBeInstanceOf(PersonName::class)
        ->and($name?->first)->toBe('Ada')
        ->and($name?->last)->toBe('Lovelace')
        ->and(PersonNameFormatter::initials($name))->toBe('AL')
        ->and(PersonNameFormatter::familiar($name))->toBe('Ada L.');
});

it('limits initials length', function (): void {
    $name = new PersonName('Mary Jane', 'Watson');

    expect(PersonNameFormatter::initials($name, 2))->toBe('MJ')
        ->and(PersonNameFormatter::initials($name, 1))->toBe('M');
});

it('provides fallbacks for empty names', function (): void {
    expect(PersonNameFormatter::full('', 'Fallback'))->toBe('Fallback')
        ->and(PersonNameFormatter::initials(null, 2, '?'))->toBe('?')
        ->and(PersonNameFormatter::first(null, ''))->toBe('');
});
