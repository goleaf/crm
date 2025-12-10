<?php

declare(strict_types=1);

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Models\Company;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('support case factory creates valid case with null relations by default', function (): void {
    $case = SupportCase::factory()->make();

    expect($case)->toBeInstanceOf(SupportCase::class)
        ->and($case->case_number)->toStartWith('CASE-')
        ->and($case->subject)->not->toBeNull()
        ->and($case->description)->not->toBeNull()
        ->and($case->status)->toBeInstanceOf(CaseStatus::class)
        ->and($case->priority)->toBeInstanceOf(CasePriority::class)
        ->and($case->type)->toBeInstanceOf(CaseType::class)
        ->and($case->channel)->toBeInstanceOf(CaseChannel::class)
        ->and($case->team_id)->toBeNull()
        ->and($case->creator_id)->toBeNull()
        ->and($case->company_id)->toBeNull()
        ->and($case->contact_id)->toBeNull()
        ->and($case->assigned_to_id)->toBeNull()
        ->and($case->assigned_team_id)->toBeNull();
});

test('support case factory withRelations creates all related models', function (): void {
    $case = SupportCase::factory()->withRelations()->create();

    expect($case->team_id)->not->toBeNull()
        ->and($case->creator_id)->not->toBeNull()
        ->and($case->company_id)->not->toBeNull()
        ->and($case->contact_id)->not->toBeNull()
        ->and($case->assigned_to_id)->not->toBeNull()
        ->and($case->assigned_team_id)->not->toBeNull()
        ->and($case->team)->toBeInstanceOf(Team::class)
        ->and($case->creator)->toBeInstanceOf(User::class)
        ->and($case->company)->toBeInstanceOf(Company::class)
        ->and($case->contact)->toBeInstanceOf(People::class)
        ->and($case->assignee)->toBeInstanceOf(User::class)
        ->and($case->assignedTeam)->toBeInstanceOf(Team::class);
});

test('support case factory withRelations uses same team for team_id and assigned_team_id', function (): void {
    $case = SupportCase::factory()->withRelations()->create();

    expect($case->team_id)->toBe($case->assigned_team_id);
});

test('support case factory forTeam creates case with specific team', function (): void {
    $team = Team::factory()->create();
    $case = SupportCase::factory()->forTeam($team)->create();

    expect($case->team_id)->toBe($team->id)
        ->and($case->assigned_team_id)->toBe($team->id);
});

test('support case factory forTeam creates new team when none provided', function (): void {
    $case = SupportCase::factory()->forTeam()->create();

    expect($case->team_id)->not->toBeNull()
        ->and($case->assigned_team_id)->toBe($case->team_id)
        ->and(Team::count())->toBe(1);
});

test('support case factory assignedToSameTeam sets assigned_team_id to team_id', function (): void {
    $team = Team::factory()->create();
    $case = SupportCase::factory()
        ->state(['team_id' => $team->id])
        ->assignedToSameTeam()
        ->create();

    expect($case->assigned_team_id)->toBe($team->id);
});

test('support case factory open state sets correct status', function (): void {
    $case = SupportCase::factory()->open()->make();

    expect($case->status)->toBe(CaseStatus::NEW)
        ->and($case->resolved_at)->toBeNull();
});

test('support case factory closed state sets correct status and resolved_at', function (): void {
    $case = SupportCase::factory()->closed()->make();

    expect($case->status)->toBe(CaseStatus::CLOSED)
        ->and($case->resolved_at)->not->toBeNull();
});

test('support case factory pendingInput state sets correct status', function (): void {
    $case = SupportCase::factory()->pendingInput()->make();

    expect($case->status)->toBe(CaseStatus::PENDING_INPUT)
        ->and($case->resolved_at)->toBeNull();
});

test('support case factory assigned state sets correct status', function (): void {
    $case = SupportCase::factory()->assigned()->make();

    expect($case->status)->toBe(CaseStatus::ASSIGNED)
        ->and($case->resolved_at)->toBeNull();
});

test('support case factory highPriority state sets P1 priority', function (): void {
    $case = SupportCase::factory()->highPriority()->make();

    expect($case->priority)->toBe(CasePriority::P1);
});

test('support case factory overdue state sets SLA breach', function (): void {
    $case = SupportCase::factory()->overdue()->make();

    expect($case->sla_due_at)->toBeBefore(now())
        ->and($case->sla_breached)->toBeTrue()
        ->and($case->resolved_at)->toBeNull();
});

test('support case factory generates unique case numbers', function (): void {
    $cases = SupportCase::factory()->count(5)->make();

    $caseNumbers = $cases->pluck('case_number')->unique();

    expect($caseNumbers)->toHaveCount(5);
});

test('support case factory generates valid queue values', function (): void {
    $case = SupportCase::factory()->make();
    $validQueues = ['general', 'billing', 'technical', 'product'];

    expect($validQueues)->toContain($case->queue);
});

test('support case factory can override default values', function (): void {
    $customSubject = 'Custom Support Case Subject';
    $customPriority = CasePriority::P2;

    $case = SupportCase::factory()->make([
        'subject' => $customSubject,
        'priority' => $customPriority,
    ]);

    expect($case->subject)->toBe($customSubject)
        ->and($case->priority)->toBe($customPriority);
});

test('support case factory can create multiple cases', function (): void {
    $team = Team::factory()->create();
    $cases = SupportCase::factory()
        ->count(5)
        ->state(['team_id' => $team->id])
        ->create();

    expect($cases)->toHaveCount(5)
        ->and(SupportCase::count())->toBe(5);
});

test('support case factory generates valid sla_due_at in future', function (): void {
    $case = SupportCase::factory()->make();

    expect($case->sla_due_at)->toBeAfter(now());
});

test('support case factory can chain multiple states', function (): void {
    $team = Team::factory()->create();
    $case = SupportCase::factory()
        ->forTeam($team)
        ->highPriority()
        ->open()
        ->create();

    expect($case->team_id)->toBe($team->id)
        ->and($case->priority)->toBe(CasePriority::P1)
        ->and($case->status)->toBe(CaseStatus::NEW);
});

test('support case factory sequence decrements timestamps', function (): void {
    $team = Team::factory()->create();
    $cases = SupportCase::factory()
        ->count(3)
        ->state(['team_id' => $team->id])
        ->create();

    // First case should have the most recent timestamp
    expect($cases[0]->created_at)->toBeAfter($cases[1]->created_at)
        ->and($cases[1]->created_at)->toBeAfter($cases[2]->created_at);
});

test('support case isOverdue returns true for overdue cases', function (): void {
    $team = Team::factory()->create();
    $case = SupportCase::factory()
        ->overdue()
        ->state(['team_id' => $team->id])
        ->create();

    expect($case->isOverdue())->toBeTrue();
});

test('support case isOverdue returns false for closed cases', function (): void {
    $team = Team::factory()->create();
    $case = SupportCase::factory()
        ->closed()
        ->state(['team_id' => $team->id, 'sla_due_at' => now()->subDay()])
        ->create();

    expect($case->isOverdue())->toBeFalse();
});

test('support case hasBreachedSla returns correct value', function (): void {
    $team = Team::factory()->create();
    $breachedCase = SupportCase::factory()
        ->overdue()
        ->state(['team_id' => $team->id])
        ->create();

    $normalCase = SupportCase::factory()
        ->open()
        ->state(['team_id' => $team->id, 'sla_breached' => false])
        ->create();

    expect($breachedCase->hasBreachedSla())->toBeTrue()
        ->and($normalCase->hasBreachedSla())->toBeFalse();
});

test('support case getTimeUntilSlaBreach returns null when closed', function (): void {
    $team = Team::factory()->create();
    $case = SupportCase::factory()
        ->closed()
        ->state(['team_id' => $team->id])
        ->create();

    expect($case->getTimeUntilSlaBreach())->toBeNull();
});

test('support case getTimeUntilSlaBreach returns minutes when not resolved', function (): void {
    $team = Team::factory()->create();
    $case = SupportCase::factory()
        ->open()
        ->state([
            'team_id' => $team->id,
            'sla_due_at' => now()->addHours(2),
        ])
        ->create();

    $timeRemaining = $case->getTimeUntilSlaBreach();

    expect($timeRemaining)->toBeGreaterThan(100)
        ->and($timeRemaining)->toBeLessThanOrEqual(120);
});
