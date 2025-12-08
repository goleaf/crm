<?php

declare(strict_types=1);

use App\Rules\NoProfanity;
use Illuminate\Support\Facades\Validator;

it('passes validation for clean text', function (): void {
    $validator = Validator::make(
        ['message' => 'This is a clean message'],
        ['message' => [new NoProfanity]]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails validation for profane text', function (): void {
    $validator = Validator::make(
        ['message' => 'This is fucking bad'],
        ['message' => [new NoProfanity]]
    );

    expect($validator->fails())->toBeTrue();
});

it('validates with specific language', function (): void {
    $validator = Validator::make(
        ['message' => 'esto es una mierda'],
        ['message' => [new NoProfanity('spanish')]]
    );

    expect($validator->fails())->toBeTrue();
});

it('validates against all languages', function (): void {
    $validator = Validator::make(
        ['message' => 'fuck merde scheiße'],
        ['message' => [new NoProfanity('all')]]
    );

    expect($validator->fails())->toBeTrue();
});

it('handles non-string values gracefully', function (): void {
    $validator = Validator::make(
        ['message' => 123],
        ['message' => [new NoProfanity]]
    );

    expect($validator->passes())->toBeTrue();
});

it('works with Laravel validation rule syntax', function (): void {
    $validator = Validator::make(
        ['comment' => 'This is fucking terrible'],
        ['comment' => ['required', 'string', 'blasp_check']]
    );

    expect($validator->fails())->toBeTrue();
});

it('works with language parameter in Laravel validation', function (): void {
    $validator = Validator::make(
        ['comment' => 'esto es mierda'],
        ['comment' => ['required', 'string', 'blasp_check:spanish']]
    );

    expect($validator->fails())->toBeTrue();
});

it('passes for clean text with Laravel validation rule', function (): void {
    $validator = Validator::make(
        ['comment' => 'This is a nice comment'],
        ['comment' => ['required', 'string', 'blasp_check']]
    );

    expect($validator->passes())->toBeTrue();
});
