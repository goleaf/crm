<?php

declare(strict_types=1);

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->lead = Lead::factory()->create(['team_id' => $this->team->id]);
});

test('authorize returns true when user can update lead', function () {
    Gate::shouldReceive('forUser')
        ->with($this->user)
        ->andReturnSelf();

    Gate::shouldReceive('check')
        ->with('update', $this->lead)
        ->andReturn(true);

    $request = new UpdateLeadRequest;
    $request->setUserResolver(fn () => $this->user);
    $request->setRouteResolver(fn () => new class
    {
        public function parameter($key)
        {
            return test()->lead;
        }
    });

    expect($request->authorize())->toBeTrue();
});

test('validates required fields', function () {
    $request = new UpdateLeadRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('source'))->toBeTrue()
        ->and($validator->errors()->has('status'))->toBeTrue()
        ->and($validator->errors()->has('assignment_strategy'))->toBeTrue();
});

test('validates name field', function () {
    $request = new UpdateLeadRequest;

    $validator = Validator::make([
        'name' => str_repeat('a', 256),
        'source' => LeadSource::WEBSITE->value,
        'status' => LeadStatus::NEW->value,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
});

test('validates email format', function () {
    $request = new UpdateLeadRequest;

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

test('passes validation with valid data', function () {
    $request = new UpdateLeadRequest;

    $validator = Validator::make([
        'name' => 'John Doe Updated',
        'email' => 'john.updated@example.com',
        'phone' => '555-5678',
        'source' => LeadSource::REFERRAL->value,
        'status' => LeadStatus::WORKING->value,
        'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN->value,
        'score' => 85,
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('has custom error messages', function () {
    $request = new UpdateLeadRequest;
    $messages = $request->messages();

    expect($messages)->toHaveKey('name.required')
        ->and($messages)->toHaveKey('email.email')
        ->and($messages)->toHaveKey('website.url')
        ->and($messages)->toHaveKey('score.min')
        ->and($messages)->toHaveKey('score.max');
});
