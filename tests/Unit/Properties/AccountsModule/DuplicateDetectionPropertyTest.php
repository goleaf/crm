<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Lead;
use App\Models\Team;
use App\Services\AccountDuplicateDetectionService;
use App\Services\LeadDuplicateDetectionService;

/**
 * **Feature: core-crm-modules, Property 10: Duplicate detection for leads/accounts**
 *
 * **Validates: Requirements 1.5, 3.4**
 *
 * Property: For any lead or account, the duplicate detection service must identify
 * similar records based on name/domain/phone with similarity scores, and surface
 * detections during create/import flows.
 */

// Property: Duplicate detection scores are bounded
test('property: duplicate detection scores are always between 0 and 100', function (): void {
    $team = Team::factory()->create();

    // Test with Accounts
    $account1 = Account::factory()->create(['team_id' => $team->id]);
    $account2 = Account::factory()->create(['team_id' => $team->id]);

    $accountService = new AccountDuplicateDetectionService;
    $accountScore = $accountService->calculateScore($account1, $account2);

    expect($accountScore)->toBeGreaterThanOrEqual(0.0)
        ->and($accountScore)->toBeLessThanOrEqual(100.0);

    // Test with Leads
    $lead1 = Lead::factory()->create(['team_id' => $team->id]);
    $lead2 = Lead::factory()->create(['team_id' => $team->id]);

    $leadService = new LeadDuplicateDetectionService;
    $leadScore = $leadService->calculateScore($lead1, $lead2);

    expect($leadScore)->toBeGreaterThanOrEqual(0.0)
        ->and($leadScore)->toBeLessThanOrEqual(100.0);
})->repeat(100);

// Property: Identical records score 100%
test('property: identical records always score 100 percent', function (): void {
    $team = Team::factory()->create();

    // Test with Accounts
    $account = Account::factory()->create(['team_id' => $team->id]);
    $accountService = new AccountDuplicateDetectionService;
    $accountScore = $accountService->calculateScore($account, $account);

    expect($accountScore)->toBe(100.0);

    // Test with Leads
    $lead = Lead::factory()->create(['team_id' => $team->id]);
    $leadService = new LeadDuplicateDetectionService;
    $leadScore = $leadService->calculateScore($lead, $lead);

    expect($leadScore)->toBe(100.0);
})->repeat(50);

// Property: Exact name matches score highly
test('property: exact name matches produce high similarity scores', function (): void {
    $team = Team::factory()->create();
    $name = fake()->company();

    // Test with Accounts
    $account1 = Account::factory()->create([
        'team_id' => $team->id,
        'name' => $name,
    ]);
    $account2 = Account::factory()->create([
        'team_id' => $team->id,
        'name' => $name,
    ]);

    $accountService = new AccountDuplicateDetectionService;
    $accountScore = $accountService->calculateScore($account1, $account2);

    expect($accountScore)->toBeGreaterThan(40.0);

    // Test with Leads
    $personName = fake()->name();
    $lead1 = Lead::factory()->create([
        'team_id' => $team->id,
        'name' => $personName,
    ]);
    $lead2 = Lead::factory()->create([
        'team_id' => $team->id,
        'name' => $personName,
    ]);

    $leadService = new LeadDuplicateDetectionService;
    $leadScore = $leadService->calculateScore($lead1, $lead2);

    expect($leadScore)->toBeGreaterThan(5.0);
})->repeat(100);

// Property: Exact email matches for leads score highly
test('property: exact email matches for leads produce high similarity scores', function (): void {
    $team = Team::factory()->create();
    $email = fake()->unique()->safeEmail();

    $lead1 = Lead::factory()->create([
        'team_id' => $team->id,
        'email' => $email,
    ]);
    $lead2 = Lead::factory()->create([
        'team_id' => $team->id,
        'email' => $email,
    ]);

    $service = new LeadDuplicateDetectionService;
    $score = $service->calculateScore($lead1, $lead2);

    expect($score)->toBeGreaterThan(60.0);
})->repeat(100);

// Property: Exact domain matches for accounts score highly
test('property: exact domain matches for accounts produce high similarity scores', function (): void {
    $team = Team::factory()->create();
    $domain = 'https://'.fake()->unique()->domainName();

    $account1 = Account::factory()->create([
        'team_id' => $team->id,
        'website' => $domain,
    ]);
    $account2 = Account::factory()->create([
        'team_id' => $team->id,
        'website' => $domain,
    ]);

    $service = new AccountDuplicateDetectionService;
    $score = $service->calculateScore($account1, $account2);

    expect($score)->toBeGreaterThan(25.0);
})->repeat(100);

// Property: Phone number matches increase similarity
test('property: matching phone numbers increase similarity scores', function (): void {
    $team = Team::factory()->create();
    $phone = fake()->unique()->phoneNumber();

    // Test with Accounts
    $account1 = Account::factory()->create([
        'team_id' => $team->id,
        'billing_address' => ['phone' => $phone],
    ]);
    $account2 = Account::factory()->create([
        'team_id' => $team->id,
        'billing_address' => ['phone' => $phone],
    ]);

    $accountService = new AccountDuplicateDetectionService;
    $accountScore = $accountService->calculateScore($account1, $account2);

    expect($accountScore)->toBeGreaterThan(15.0);

    // Test with Leads
    $lead1 = Lead::factory()->create([
        'team_id' => $team->id,
        'phone' => $phone,
    ]);
    $lead2 = Lead::factory()->create([
        'team_id' => $team->id,
        'phone' => $phone,
    ]);

    $leadService = new LeadDuplicateDetectionService;
    $leadScore = $leadService->calculateScore($lead1, $lead2);

    expect($leadScore)->toBeGreaterThan(15.0);
})->repeat(100);

// Property: Duplicate detection respects team boundaries
test('property: duplicate detection only finds records within the same team', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    // Test with Accounts
    $account = Account::factory()->create(['team_id' => $team1->id]);
    Account::factory()->count(5)->create(['team_id' => $team2->id]);

    $accountService = new AccountDuplicateDetectionService;
    $accountDuplicates = $accountService->find($account, threshold: 0.0);

    expect($accountDuplicates)->toBeEmpty();

    // Test with Leads
    $lead = Lead::factory()->create(['team_id' => $team1->id]);
    Lead::factory()->count(5)->create(['team_id' => $team2->id]);

    $leadService = new LeadDuplicateDetectionService;
    $leadDuplicates = $leadService->find($lead, threshold: 0.0);

    expect($leadDuplicates)->toBeEmpty();
})->repeat(50);

// Property: Threshold filtering works correctly
test('property: duplicate detection respects threshold parameter', function (): void {
    $team = Team::factory()->create();

    // Test with Accounts
    $account = Account::factory()->create(['team_id' => $team->id]);
    Account::factory()->count(10)->create(['team_id' => $team->id]);

    $accountService = new AccountDuplicateDetectionService;
    $lowThreshold = $accountService->find($account, threshold: 10.0);
    $highThreshold = $accountService->find($account, threshold: 90.0);

    expect($lowThreshold->count())->toBeGreaterThanOrEqual($highThreshold->count());

    // Test with Leads
    $lead = Lead::factory()->create(['team_id' => $team->id]);
    Lead::factory()->count(10)->create(['team_id' => $team->id]);

    $leadService = new LeadDuplicateDetectionService;
    $lowThreshold = $leadService->find($lead, threshold: 10.0);
    $highThreshold = $leadService->find($lead, threshold: 90.0);

    expect($lowThreshold->count())->toBeGreaterThanOrEqual($highThreshold->count());
})->repeat(50);

// Property: Limit parameter constrains results
test('property: duplicate detection respects limit parameter', function (): void {
    $team = Team::factory()->create();
    $limit = fake()->numberBetween(1, 5);

    // Test with Accounts
    $account = Account::factory()->create(['team_id' => $team->id]);
    Account::factory()->count(20)->create(['team_id' => $team->id]);

    $accountService = new AccountDuplicateDetectionService;
    $accountDuplicates = $accountService->find($account, threshold: 0.0, limit: $limit);

    expect($accountDuplicates->count())->toBeLessThanOrEqual($limit);

    // Test with Leads
    $lead = Lead::factory()->create(['team_id' => $team->id]);
    Lead::factory()->count(20)->create(['team_id' => $team->id]);

    $leadService = new LeadDuplicateDetectionService;
    $leadDuplicates = $leadService->find($lead, threshold: 0.0, limit: $limit);

    expect($leadDuplicates->count())->toBeLessThanOrEqual($limit);
})->repeat(50);

// Property: Similarity is symmetric
test('property: similarity scores are symmetric', function (): void {
    $team = Team::factory()->create();

    // Test with Accounts
    $account1 = Account::factory()->create(['team_id' => $team->id]);
    $account2 = Account::factory()->create(['team_id' => $team->id]);

    $accountService = new AccountDuplicateDetectionService;
    $scoreAB = $accountService->calculateScore($account1, $account2);
    $scoreBA = $accountService->calculateScore($account2, $account1);

    expect($scoreAB)->toBe($scoreBA);

    // Test with Leads
    $lead1 = Lead::factory()->create(['team_id' => $team->id]);
    $lead2 = Lead::factory()->create(['team_id' => $team->id]);

    $leadService = new LeadDuplicateDetectionService;
    $scoreAB = $leadService->calculateScore($lead1, $lead2);
    $scoreBA = $leadService->calculateScore($lead2, $lead1);

    expect($scoreAB)->toBe($scoreBA);
})->repeat(100);

// Property: Results are sorted by score descending
test('property: duplicate detection results are sorted by score in descending order', function (): void {
    $team = Team::factory()->create();

    // Test with Accounts
    $account = Account::factory()->create(['team_id' => $team->id]);
    Account::factory()->count(10)->create(['team_id' => $team->id]);

    $accountService = new AccountDuplicateDetectionService;
    $accountDuplicates = $accountService->find($account, threshold: 0.0);

    $scores = $accountDuplicates->pluck('score')->toArray();
    $sortedScores = collect($scores)->sortDesc()->values()->toArray();

    expect($scores)->toBe($sortedScores);

    // Test with Leads
    $lead = Lead::factory()->create(['team_id' => $team->id]);
    Lead::factory()->count(10)->create(['team_id' => $team->id]);

    $leadService = new LeadDuplicateDetectionService;
    $leadDuplicates = $leadService->find($lead, threshold: 0.0);

    $scores = $leadDuplicates->pluck('score')->toArray();
    $sortedScores = collect($scores)->sortDesc()->values()->toArray();

    expect($scores)->toBe($sortedScores);
})->repeat(50);

// Property: Case-insensitive matching
test('property: duplicate detection is case-insensitive', function (): void {
    $team = Team::factory()->create();

    // Test with Accounts
    $name = fake()->company();
    $account1 = Account::factory()->create([
        'team_id' => $team->id,
        'name' => strtoupper($name),
    ]);
    $account2 = Account::factory()->create([
        'team_id' => $team->id,
        'name' => strtolower($name),
    ]);

    $accountService = new AccountDuplicateDetectionService;
    $accountScore = $accountService->calculateScore($account1, $account2);

    expect($accountScore)->toBeGreaterThan(40.0);

    // Test with Leads
    $email = fake()->unique()->safeEmail();
    $lead1 = Lead::factory()->create([
        'team_id' => $team->id,
        'email' => strtoupper($email),
    ]);
    $lead2 = Lead::factory()->create([
        'team_id' => $team->id,
        'email' => strtolower($email),
    ]);

    $leadService = new LeadDuplicateDetectionService;
    $leadScore = $leadService->calculateScore($lead1, $lead2);

    expect($leadScore)->toBeGreaterThan(60.0);
})->repeat(100);
