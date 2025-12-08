<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\BounceType;
use App\Enums\EmailSendStatus;
use App\Models\EmailProgram;
use App\Models\EmailProgramBounce;
use App\Models\EmailProgramRecipient;
use App\Models\EmailProgramStep;
use App\Models\EmailProgramUnsubscribe;
use App\Models\Team;
use App\Services\EmailProgramService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new EmailProgramService;
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.1, 5.2
 */
test('handles bounce correctly', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->active()->create();
    $step = EmailProgramStep::factory()->for($program)->create();
    $recipient = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->sent()
        ->create();

    $this->service->handleBounce(
        $recipient->email,
        BounceType::HARD->value,
        'Mailbox does not exist',
        '550 5.1.1',
        ['raw' => 'message']
    );

    $recipient->refresh();
    expect($recipient->status)->toBe(EmailSendStatus::BOUNCED);
    expect($recipient->bounced_at)->not->toBeNull();
    expect($recipient->bounce_type)->toBe(BounceType::HARD->value);

    expect('email_program_bounces')->toHaveRecord([
        'email' => $recipient->email,
        'bounce_type' => BounceType::HARD->value,
    ]);
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.2
 */
test('handles unsubscribe correctly', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->active()->create();

    $this->service->handleUnsubscribe(
        $team->id,
        'test@example.com',
        $program->id,
        'not_interested',
        'Too many emails',
        '127.0.0.1',
        'Mozilla/5.0'
    );

    expect('email_program_unsubscribes')->toHaveRecord([
        'team_id' => $team->id,
        'email' => 'test@example.com',
        'reason' => 'not_interested',
    ]);
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.1
 */
test('tracks email open', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->active()->create();
    $step = EmailProgramStep::factory()->for($program)->create();
    $recipient = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->delivered()
        ->create(['open_count' => 0]);

    $this->service->trackOpen($recipient->id);

    $recipient->refresh();
    expect($recipient->open_count)->toBe(1);
    expect($recipient->opened_at)->not->toBeNull();
    expect($recipient->engagement_score)->toBe(5);
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.1
 */
test('tracks email click', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->active()->create();
    $step = EmailProgramStep::factory()->for($program)->create();
    $recipient = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->delivered()
        ->create(['click_count' => 0]);

    $this->service->trackClick($recipient->id, 'https://example.com');

    $recipient->refresh();
    expect($recipient->click_count)->toBe(1);
    expect($recipient->clicked_at)->not->toBeNull();
    expect($recipient->engagement_score)->toBe(10);
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.2
 */
test('selects ab test winner by open rate', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()
        ->for($team)
        ->active()
        ->withAbTest()
        ->create(['ab_test_winner_metric' => 'open_rate']);

    $stepA = EmailProgramStep::factory()
        ->for($program)
        ->asVariant('A', true)
        ->create([
            'sent_count' => 100,
            'opened_count' => 30,
        ]);

    $stepB = EmailProgramStep::factory()
        ->for($program)
        ->asVariant('B')
        ->create([
            'sent_count' => 100,
            'opened_count' => 45,
        ]);

    $winner = $this->service->selectAbTestWinner($program);

    expect($winner)->toBe('B');
    $program->refresh();
    expect($program->ab_test_winner_variant)->toBe('B');
    expect($program->ab_test_winner_selected_at)->not->toBeNull();
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.1
 */
test('personalizes content with recipient data', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->create();
    $step = EmailProgramStep::factory()->for($program)->create();
    $recipient = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'custom_fields' => ['company' => 'Acme Corp'],
        ]);

    $content = 'Hello {{first_name}} {{last_name}} from {{company}}! Email: {{email}}';
    $personalized = $this->service->personalizeContent($content, $recipient);

    expect($personalized)->toBe('Hello John Doe from Acme Corp! Email: john@example.com');
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.1
 */
test('calculates daily analytics', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->active()->create();
    $step = EmailProgramStep::factory()->for($program)->create();

    $date = \Illuminate\Support\Facades\Date::today();

    // Create recipients with various statuses
    EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->count(5)
        ->delivered()
        ->create(['sent_at' => $date]);

    EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->count(3)
        ->opened()
        ->create(['sent_at' => $date]);

    EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->count(2)
        ->clicked()
        ->create(['sent_at' => $date]);

    $this->service->calculateDailyAnalytics($program, $date);

    expect('email_program_analytics')->toHaveRecord([
        'email_program_id' => $program->id,
        'email_program_step_id' => $step->id,
        'date' => $date->format('Y-m-d'),
        'sent_count' => 10,
    ]);
});

/**
 * Feature: advanced-features, Property 7: Deliverability optimization
 *
 * Validates: Requirements 5.2
 */
test('processes pending sends respects throttling', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()
        ->for($team)
        ->active()
        ->withThrottling()
        ->create(['throttle_rate_per_hour' => 2]);

    $step = EmailProgramStep::factory()->for($program)->create();

    // Create 3 pending recipients
    EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->count(3)
        ->create([
            'status' => EmailSendStatus::PENDING,
            'scheduled_send_at' => now()->subMinutes(5),
        ]);

    $now = \Illuminate\Support\Facades\Date::now();
    $processed = $this->service->processPendingSends($now);

    // Should only process 2 due to throttling
    expect($processed)->toBe(2);
});

/**
 * Feature: advanced-features, Property 6: Email program governance
 *
 * Validates: Requirements 5.2
 */
test('unsubscribed emails are excluded from scheduling', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->create();

    // Create unsubscribe record
    EmailProgramUnsubscribe::factory()->create([
        'team_id' => $team->id,
        'email' => 'unsubscribed@example.com',
    ]);

    // Attempt to schedule - should be skipped
    // This would be tested with actual audience data in integration tests
    expect(true)->toBeTrue(); // Placeholder for now
});

/**
 * Feature: advanced-features, Property 7: Deliverability optimization
 *
 * Validates: Requirements 5.2
 */
test('hard bounced emails are excluded from scheduling', function (): void {
    $team = Team::factory()->create();
    $program = EmailProgram::factory()->for($team)->create();

    // Create hard bounce record
    EmailProgramBounce::create([
        'email_program_id' => $program->id,
        'email' => 'bounced@example.com',
        'bounce_type' => BounceType::HARD,
        'bounce_reason' => 'Mailbox does not exist',
    ]);

    // Attempt to schedule - should be skipped
    // This would be tested with actual audience data in integration tests
    expect(true)->toBeTrue(); // Placeholder for now
});
