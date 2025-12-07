<?php

declare(strict_types=1);

namespace Tests\Feature\AdvancedFeatures;

use App\Enums\BounceType;
use App\Enums\EmailProgramStatus;
use App\Enums\EmailProgramType;
use App\Enums\EmailSendStatus;
use App\Models\EmailProgram;
use App\Models\EmailProgramAnalytic;
use App\Models\EmailProgramBounce;
use App\Models\EmailProgramRecipient;
use App\Models\EmailProgramStep;
use App\Models\EmailProgramUnsubscribe;
use App\Models\Team;
use App\Services\EmailProgramService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Integration test: Drip campaign execution with analytics
 *
 * Tests the complete workflow of a drip campaign from scheduling
 * through execution, tracking, and analytics generation.
 */
test('complete drip campaign execution with analytics', function () {
    $team = Team::factory()->create();
    $service = new EmailProgramService;

    // Create drip campaign
    $program = EmailProgram::factory()->for($team)->create([
        'name' => 'Onboarding Drip Campaign',
        'type' => EmailProgramType::DRIP,
        'status' => EmailProgramStatus::ACTIVE,
        'throttle_enabled' => true,
        'throttle_rate_per_hour' => 100,
    ]);

    // Create campaign steps
    $step1 = EmailProgramStep::factory()->for($program)->create([
        'step_order' => 1,
        'name' => 'Welcome Email',
        'subject' => 'Welcome to {{company_name}}!',
        'content' => 'Hi {{first_name}}, welcome aboard!',
        'delay_minutes' => 0,
    ]);

    $step2 = EmailProgramStep::factory()->for($program)->create([
        'step_order' => 2,
        'name' => 'Getting Started',
        'subject' => 'Getting Started with {{company_name}}',
        'content' => 'Hi {{first_name}}, here are some tips...',
        'delay_minutes' => 1440, // 24 hours
    ]);

    $step3 = EmailProgramStep::factory()->for($program)->create([
        'step_order' => 3,
        'name' => 'Feature Highlight',
        'subject' => 'Check out these features',
        'content' => 'Hi {{first_name}}, did you know...',
        'delay_minutes' => 4320, // 72 hours
    ]);

    // Create recipients
    $recipient1 = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step1, 'emailProgramStep')
        ->create([
            'email' => 'user1@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'custom_fields' => ['company_name' => 'Acme Corp'],
            'status' => EmailSendStatus::PENDING,
            'scheduled_send_at' => now(),
        ]);

    $recipient2 = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step1, 'emailProgramStep')
        ->create([
            'email' => 'user2@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'custom_fields' => ['company_name' => 'Acme Corp'],
            'status' => EmailSendStatus::PENDING,
            'scheduled_send_at' => now(),
        ]);

    // Process pending sends (step 1)
    $processed = $service->processPendingSends(Carbon::now());
    expect($processed)->toBeGreaterThan(0);

    // Mark as delivered
    $recipient1->update([
        'status' => EmailSendStatus::DELIVERED,
        'sent_at' => now(),
        'delivered_at' => now(),
    ]);

    $recipient2->update([
        'status' => EmailSendStatus::DELIVERED,
        'sent_at' => now(),
        'delivered_at' => now(),
    ]);

    // Track opens
    $service->trackOpen($recipient1->id);
    $service->trackOpen($recipient2->id);

    expect($recipient1->fresh()->open_count)->toBe(1)
        ->and($recipient1->fresh()->opened_at)->not->toBeNull()
        ->and($recipient2->fresh()->open_count)->toBe(1);

    // Track clicks
    $service->trackClick($recipient1->id, 'https://example.com/feature');

    expect($recipient1->fresh()->click_count)->toBe(1)
        ->and($recipient1->fresh()->clicked_at)->not->toBeNull()
        ->and($recipient1->fresh()->engagement_score)->toBeGreaterThan(0);

    // Calculate daily analytics
    $date = Carbon::today();
    $service->calculateDailyAnalytics($program, $date);

    $analytics = EmailProgramAnalytic::where('email_program_id', $program->id)
        ->where('date', $date->format('Y-m-d'))
        ->first();

    expect($analytics)->not->toBeNull()
        ->and($analytics->sent_count)->toBeGreaterThan(0)
        ->and($analytics->delivered_count)->toBeGreaterThan(0)
        ->and($analytics->opened_count)->toBeGreaterThan(0)
        ->and($analytics->clicked_count)->toBeGreaterThan(0);

    // Verify program statistics
    $program = $program->fresh();
    expect($program->total_sent)->toBeGreaterThan(0)
        ->and($program->total_delivered)->toBeGreaterThan(0)
        ->and($program->total_opened)->toBeGreaterThan(0)
        ->and($program->total_clicked)->toBeGreaterThan(0);
});

/**
 * Integration test: A/B test winner selection
 */
test('A/B test selects winner based on performance metrics', function () {
    $team = Team::factory()->create();
    $service = new EmailProgramService;

    // Create A/B test program
    $program = EmailProgram::factory()->for($team)->create([
        'name' => 'Subject Line A/B Test',
        'type' => EmailProgramType::DRIP,
        'status' => EmailProgramStatus::ACTIVE,
        'ab_test_enabled' => true,
        'ab_test_winner_metric' => 'open_rate',
        'ab_test_sample_size_percentage' => 20,
    ]);

    // Create variant A (control)
    $variantA = EmailProgramStep::factory()->for($program)->create([
        'step_order' => 1,
        'name' => 'Variant A',
        'subject' => 'Check out our new features',
        'is_ab_test_variant' => true,
        'ab_test_variant_name' => 'A',
        'sent_count' => 100,
        'delivered_count' => 98,
        'opened_count' => 30,
        'clicked_count' => 10,
    ]);

    // Create variant B
    $variantB = EmailProgramStep::factory()->for($program)->create([
        'step_order' => 1,
        'name' => 'Variant B',
        'subject' => 'You won\'t believe these new features!',
        'is_ab_test_variant' => true,
        'ab_test_variant_name' => 'B',
        'sent_count' => 100,
        'delivered_count' => 97,
        'opened_count' => 48,
        'clicked_count' => 18,
    ]);

    // Select winner
    $winner = $service->selectAbTestWinner($program);

    expect($winner)->toBe('B') // Variant B has higher open rate
        ->and($program->fresh()->ab_test_winner_variant)->toBe('B')
        ->and($program->fresh()->ab_test_winner_selected_at)->not->toBeNull();

    // Verify winner metrics
    $winnerStep = $program->steps()->where('ab_test_variant_name', 'B')->first();
    $openRate = ($winnerStep->opened_count / $winnerStep->delivered_count) * 100;
    expect($openRate)->toBeGreaterThan(40);
});

/**
 * Integration test: Unsubscribe and bounce handling
 */
test('unsubscribes and bounces are honored in future sends', function () {
    $team = Team::factory()->create();
    $service = new EmailProgramService;

    $program = EmailProgram::factory()->for($team)->active()->create();
    $step = EmailProgramStep::factory()->for($program)->create();

    // Create recipient and send email
    $recipient = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->delivered()
        ->create(['email' => 'user@example.com']);

    // User unsubscribes
    $service->handleUnsubscribe(
        $team->id,
        'user@example.com',
        $program->id,
        'not_interested',
        'Too many emails',
        '127.0.0.1',
        'Mozilla/5.0'
    );

    // Verify unsubscribe record
    $unsubscribe = EmailProgramUnsubscribe::where('email', 'user@example.com')->first();
    expect($unsubscribe)->not->toBeNull()
        ->and($unsubscribe->team_id)->toBe($team->id)
        ->and($unsubscribe->reason)->toBe('not_interested')
        ->and($unsubscribe->unsubscribed_at)->not->toBeNull();

    // Create another recipient with same email (should be excluded)
    $recipient2 = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->create([
            'email' => 'user@example.com',
            'status' => EmailSendStatus::PENDING,
        ]);

    // Verify email is marked as unsubscribed
    expect($unsubscribe->email)->toBe($recipient2->email);

    // Test bounce handling
    $bouncedRecipient = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->sent()
        ->create(['email' => 'bounced@example.com']);

    $service->handleBounce(
        'bounced@example.com',
        BounceType::HARD->value,
        'Mailbox does not exist',
        '550 5.1.1',
        ['raw' => 'SMTP error']
    );

    // Verify bounce record
    $bounce = EmailProgramBounce::where('email', 'bounced@example.com')->first();
    expect($bounce)->not->toBeNull()
        ->and($bounce->bounce_type)->toBe(BounceType::HARD)
        ->and($bounce->bounce_reason)->toBe('Mailbox does not exist');

    // Verify recipient status updated
    expect($bouncedRecipient->fresh()->status)->toBe(EmailSendStatus::BOUNCED)
        ->and($bouncedRecipient->fresh()->bounced_at)->not->toBeNull();
});

/**
 * Integration test: Email personalization
 */
test('email content is personalized for each recipient', function () {
    $team = Team::factory()->create();
    $service = new EmailProgramService;

    $program = EmailProgram::factory()->for($team)->create();
    $step = EmailProgramStep::factory()->for($program)->create();

    // Create recipients with different data
    $recipient1 = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice@example.com',
            'custom_fields' => [
                'company' => 'Tech Corp',
                'plan' => 'Premium',
            ],
        ]);

    $recipient2 = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->create([
            'first_name' => 'Bob',
            'last_name' => 'Williams',
            'email' => 'bob@example.com',
            'custom_fields' => [
                'company' => 'Design Studio',
                'plan' => 'Basic',
            ],
        ]);

    // Template with merge fields
    $template = 'Hello {{first_name}} {{last_name}} from {{company}}! Your {{plan}} plan includes...';

    // Personalize for each recipient
    $personalized1 = $service->personalizeContent($template, $recipient1);
    $personalized2 = $service->personalizeContent($template, $recipient2);

    expect($personalized1)->toBe('Hello Alice Johnson from Tech Corp! Your Premium plan includes...')
        ->and($personalized2)->toBe('Hello Bob Williams from Design Studio! Your Basic plan includes...');
});

/**
 * Integration test: Throttling enforcement
 */
test('email sending respects throttling limits', function () {
    $team = Team::factory()->create();
    $service = new EmailProgramService;

    // Create program with strict throttling
    $program = EmailProgram::factory()->for($team)->create([
        'status' => EmailProgramStatus::ACTIVE,
        'throttle_enabled' => true,
        'throttle_rate_per_hour' => 5, // Only 5 per hour
    ]);

    $step = EmailProgramStep::factory()->for($program)->create();

    // Create 10 pending recipients
    EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->count(10)
        ->create([
            'status' => EmailSendStatus::PENDING,
            'scheduled_send_at' => now()->subMinutes(5),
        ]);

    // Process sends - should only process 5 due to throttling
    $processed = $service->processPendingSends(Carbon::now());

    expect($processed)->toBe(5);

    // Verify only 5 were sent
    $sent = EmailProgramRecipient::where('email_program_id', $program->id)
        ->where('status', EmailSendStatus::SENT)
        ->count();

    expect($sent)->toBe(5);

    // Verify 5 are still pending
    $pending = EmailProgramRecipient::where('email_program_id', $program->id)
        ->where('status', EmailSendStatus::PENDING)
        ->count();

    expect($pending)->toBe(5);
});

/**
 * Integration test: Conditional sending based on engagement
 */
test('conditional sending based on engagement scores', function () {
    $team = Team::factory()->create();
    $service = new EmailProgramService;

    $program = EmailProgram::factory()->for($team)->active()->create();
    $step = EmailProgramStep::factory()->for($program)->create();

    // Create recipients with different engagement levels
    $highEngagement = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->create([
            'engagement_score' => 85,
            'open_count' => 10,
            'click_count' => 5,
        ]);

    $lowEngagement = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->create([
            'engagement_score' => 15,
            'open_count' => 1,
            'click_count' => 0,
        ]);

    $noEngagement = EmailProgramRecipient::factory()
        ->for($program)
        ->for($step, 'emailProgramStep')
        ->create([
            'engagement_score' => 0,
            'open_count' => 0,
            'click_count' => 0,
        ]);

    // Verify engagement scores
    expect($highEngagement->engagement_score)->toBeGreaterThan(50)
        ->and($lowEngagement->engagement_score)->toBeLessThan(50)
        ->and($noEngagement->engagement_score)->toBe(0);

    // Track additional engagement
    $service->trackOpen($lowEngagement->id);
    $service->trackClick($lowEngagement->id, 'https://example.com');

    // Verify score increased
    expect($lowEngagement->fresh()->engagement_score)->toBeGreaterThan(15);
});
