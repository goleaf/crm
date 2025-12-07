<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Team;
use App\Services\LeadDuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new LeadDuplicateDetectionService;
    $this->team = Team::factory()->create();
});

it('scores identical leads very highly', function () {
    $primary = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThan(90);
});

it('finds duplicates above a threshold', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Bob Johnson',
        'email' => 'bob@example.com',
    ]);

    $duplicates = $this->service->find($lead, threshold: 40.0);

    expect($duplicates->isNotEmpty())->toBeTrue()
        ->and($duplicates->first()['lead']->getKey())->toBe($duplicate->getKey())
        ->and($duplicates->first()['score'])->toBeGreaterThan(40);
});

it('detects duplicates by email', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'J. Doe',
        'email' => 'john@example.com',
    ]);

    $score = $this->service->calculateScore($lead, $duplicate);

    expect($score)->toBeGreaterThan(60);
});

it('detects duplicates by phone number', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'phone' => '+1 (555) 123-4567',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Jane Doe',
        'phone' => '555-123-4567',
    ]);

    $score = $this->service->calculateScore($lead, $duplicate);

    expect($score)->toBeGreaterThan(5);
});

it('detects duplicates by mobile number', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'mobile' => '555-987-6543',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Jane Doe',
        'mobile' => '5559876543',
    ]);

    $score = $this->service->calculateScore($lead, $duplicate);

    expect($score)->toBeGreaterThan(5);
});

it('handles empty lead names gracefully', function () {
    $primary = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => '',
    ]);
    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => '',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100);
});

it('handles null email values', function () {
    $primary = Lead::factory()->create([
        'team_id' => $this->team->id,
        'email' => null,
    ]);
    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'email' => null,
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100);
});

it('respects threshold parameter in find', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Lead',
        'email' => 'test@example.com',
    ]);

    $highSimilarity = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Lead',
        'email' => 'test@example.com',
    ]);

    $lowSimilarity = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Different Person',
        'email' => 'different@example.com',
    ]);

    $highThreshold = $this->service->find($lead, threshold: 80.0);
    $lowThreshold = $this->service->find($lead, threshold: 10.0);

    expect($highThreshold->count())->toBeLessThanOrEqual($lowThreshold->count());
});

it('respects limit parameter in find', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Base Lead',
        'email' => 'base@example.com',
    ]);

    // Create 10 similar leads
    for ($i = 0; $i < 10; $i++) {
        Lead::factory()->create([
            'team_id' => $this->team->id,
            'name' => "Base Lead {$i}",
            'email' => 'base@example.com',
        ]);
    }

    $limited = $this->service->find($lead, threshold: 10.0, limit: 3);

    expect($limited->count())->toBeLessThanOrEqual(3);
});

it('handles identical lead IDs correctly', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $score = $this->service->calculateScore($lead, $lead);

    expect($score)->toBe(100.0);
});

it('only finds duplicates within the same team', function () {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $lead = Lead::factory()->create([
        'team_id' => $team1->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Create duplicate in different team
    Lead::factory()->create([
        'team_id' => $team2->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $duplicates = $this->service->find($lead, threshold: 50.0);

    expect($duplicates)->toBeEmpty();
});

it('normalizes email addresses correctly', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'email' => 'JOHN@EXAMPLE.COM',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Jane Doe',
        'email' => 'john@example.com',
    ]);

    $score = $this->service->calculateScore($lead, $duplicate);

    expect($score)->toBeGreaterThan(60);
});

it('handles invalid email formats', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'email' => 'not-an-email',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Jane Doe',
        'email' => 'also-not-an-email',
    ]);

    $score = $this->service->calculateScore($lead, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100);
});

it('handles boundary similarity scores', function () {
    $primary = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'A',
    ]);
    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Z',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0.0)
        ->and($score)->toBeLessThanOrEqual(100.0);
});

it('matches phone and mobile numbers interchangeably', function () {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'John Doe',
        'phone' => '555-1234',
        'mobile' => null,
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Jane Doe',
        'phone' => null,
        'mobile' => '5551234',
    ]);

    $score = $this->service->calculateScore($lead, $duplicate);

    expect($score)->toBeGreaterThan(15);
});
