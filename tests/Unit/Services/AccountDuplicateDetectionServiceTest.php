<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Team;
use App\Services\AccountDuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new AccountDuplicateDetectionService;
    $this->team = Team::factory()->create();
});

it('scores identical accounts very highly', function (): void {
    $primary = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Acme Corporation',
        'website' => 'https://acme.example.com',
    ]);

    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Acme Corporation',
        'website' => 'https://acme.example.com',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThan(75);
});

it('finds duplicates above a threshold', function (): void {
    $account = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Northern Lights',
        'website' => 'https://northern.example.com',
    ]);

    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Northern Lights LLC',
        'website' => 'https://northern.example.com',
    ]);

    Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Other Corp',
        'website' => 'https://other.example.com',
    ]);

    $duplicates = $this->service->find($account, threshold: 40.0);

    expect($duplicates->isNotEmpty())->toBeTrue()
        ->and($duplicates->first()['account']->getKey())->toBe($duplicate->getKey())
        ->and($duplicates->first()['score'])->toBeGreaterThan(40);
});

it('detects duplicates by phone number', function (): void {
    $account = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Company A',
        'billing_address' => ['phone' => '+1 (555) 123-4567'],
    ]);

    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Company B',
        'billing_address' => ['phone' => '555-123-4567'],
    ]);

    $score = $this->service->calculateScore($account, $duplicate);

    expect($score)->toBeGreaterThan(15);
});

it('handles empty account names gracefully', function (): void {
    $primary = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => '',
    ]);
    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => '',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100);
});

it('handles null website values', function (): void {
    $primary = Account::factory()->create([
        'team_id' => $this->team->id,
        'website' => null,
    ]);
    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'website' => null,
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0)
        ->and($score)->toBeLessThanOrEqual(100);
});

it('handles special characters in account names', function (): void {
    $primary = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Acme & Co., Inc.',
    ]);
    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'ACME and Company Incorporated',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThan(15);
});

it('respects threshold parameter in find', function (): void {
    $account = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Company',
        'website' => 'https://test.example.com',
    ]);

    $highSimilarity = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Company Inc',
        'website' => 'https://test.example.com',
    ]);

    $lowSimilarity = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Different Corp',
        'website' => 'https://different.example.com',
    ]);

    $highThreshold = $this->service->find($account, threshold: 80.0);
    $lowThreshold = $this->service->find($account, threshold: 10.0);

    expect($highThreshold->count())->toBeLessThanOrEqual($lowThreshold->count());
});

it('respects limit parameter in find', function (): void {
    $account = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Base Company',
    ]);

    // Create 10 similar accounts
    for ($i = 0; $i < 10; $i++) {
        Account::factory()->create([
            'team_id' => $this->team->id,
            'name' => "Base Company {$i}",
            'website' => $account->website,
        ]);
    }

    $limited = $this->service->find($account, threshold: 10.0, limit: 3);

    expect($limited->count())->toBeLessThanOrEqual(3);
});

it('handles identical account IDs correctly', function (): void {
    $account = Account::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $score = $this->service->calculateScore($account, $account);

    expect($score)->toBe(100.0);
});

it('normalizes website domains correctly', function (): void {
    $primary = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Corp',
        'website' => 'https://www.example.com/path',
    ]);

    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Corp',
        'website' => 'http://example.com',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(75);
});

it('handles subdomain variations', function (): void {
    $primary = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Corp',
        'website' => 'https://app.example.com',
    ]);

    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Corp',
        'website' => 'https://www.example.com',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThan(65);
});

it('only finds duplicates within the same team', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $account = Account::factory()->create([
        'team_id' => $team1->id,
        'name' => 'Acme Corp',
        'website' => 'https://acme.example.com',
    ]);

    // Create duplicate in different team
    Account::factory()->create([
        'team_id' => $team2->id,
        'name' => 'Acme Corp',
        'website' => 'https://acme.example.com',
    ]);

    $duplicates = $this->service->find($account, threshold: 50.0);

    expect($duplicates)->toBeEmpty();
});

it('handles phone numbers in shipping address', function (): void {
    $account = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Company A',
        'shipping_address' => ['phone' => '+1-555-987-6543'],
    ]);

    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Company B',
        'shipping_address' => ['phone' => '5559876543'],
    ]);

    $score = $this->service->calculateScore($account, $duplicate);

    expect($score)->toBeGreaterThan(15);
});

it('handles multiple phone numbers across addresses', function (): void {
    $account = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Company A',
        'billing_address' => ['phone' => '555-111-2222'],
        'shipping_address' => ['phone' => '555-333-4444'],
    ]);

    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Company B',
        'billing_address' => ['phone' => '555-333-4444'],
    ]);

    $score = $this->service->calculateScore($account, $duplicate);

    expect($score)->toBeGreaterThan(15);
});

it('handles boundary similarity scores', function (): void {
    $primary = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'A',
    ]);
    $duplicate = Account::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Z',
    ]);

    $score = $this->service->calculateScore($primary, $duplicate);

    expect($score)->toBeGreaterThanOrEqual(0.0)
        ->and($score)->toBeLessThanOrEqual(100.0);
});
