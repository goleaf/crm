<?php

declare(strict_types=1);

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Requests\StoreLeadRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

test('authorize returns true when user can create lead', function (): void {
    Gate::shouldReceive('forUser')
        ->with($this->user)
        ->andReturnSelf();

    Gate::shouldReceive('check')
        ->with('create', \App\Models\Lead::class)
        ->andReturn(true);

    $request = new StoreLeadRequest;
    $request->setUserResolver(fn () => $this->user);

    expect($request->authorize())->toBeTrue();
});

test('validates required fields', function (): void {
    $request = new StoreLeadRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('source'))->toBeTrue()
        ->and($validator->errors()->has('status'))->toBeTrue()
        ->and($validator->errors()->has('assignment_strategy'))->toBeTrue();
});

test('validates name field', function (): void {
    $request = new StoreLeadRequest;

    $validator = Validator::make([
        'name' => str_repeat('a', 256),
        'source' => LeadSource::WEBSITE->value,
        'status' => LeadStatus::NEW->value,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
});

test('validates email format', function (): void {
    $request = new StoreLeadRequest;

    $validator = Validator::make([
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'source' => LeadSource::WEBSITE->value,
        'status' => LeadStatus::NEW->value,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

test('validates website url format', function (): void {
    $request = new StoreLeadRequest;

    $validator = Validator::make([
        'name' => 'John Doe',
        'website' => 'not-a-url',
        'source' => LeadSource::WEBSITE->value,
        'status' => LeadStatus::NEW->value,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('website'))->toBeTrue();
});

test('validates score range', function (): void {
    $request = new StoreLeadRequest;

    $validator = Validator::make([
        'name' => 'John Doe',
        'score' => 1001,
        'source' => LeadSource::WEBSITE->value,
        'status' => LeadStatus::NEW->value,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('score'))->toBeTrue();
});

test('validates duplicate score range', function (): void {
    $request = new StoreLeadRequest;

    $validator = Validator::make([
        'name' => 'John Doe',
        'duplicate_score' => 101,
        'source' => LeadSource::WEBSITE->value,
        'status' => LeadStatus::NEW->value,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('duplicate_score'))->toBeTrue();
});

test('passes validation with valid data', function (): void {
    $request = new StoreLeadRequest;

    $validator = Validator::make([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
        'source' => LeadSource::WEBSITE->value,
        'status' => LeadStatus::NEW->value,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        'score' => 75,
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('has custom error messages', function (): void {
    $request = new StoreLeadRequest;
    $messages = $request->messages();

    expect($messages)->toHaveKey('name.required')
        ->and($messages)->toHaveKey('email.email')
        ->and($messages)->toHaveKey('website.url')
        ->and($messages)->toHaveKey('score.min')
        ->and($messages)->toHaveKey('score.max');
});
