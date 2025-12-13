<?php

declare(strict_types=1);

use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;
use App\Services\Opportunities\OpportunityMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->switchTeam($this->team);
    $this->actingAs($this->user);
});

/**
 * Integration test for opportunity forecast rollup calculations.
 *
 * **Validates: Requirements 4.3, 4.5**
 *
 * Tests the complete forecast calculation pipeline including
 * weighted revenue, stage progression, and dashboard integration.
 */
test('opportunity forecast calculates weighted revenue correctly', function (): void {
    // Create opportunities with different stages and probabilities
    $opportunities = [
        [
            'name' => 'Deal 1',
            'stage' => 'prospecting',
            'amount' => 100000,
            'probability' => 25,
            'expected_close_date' => now()->addDays(30),
        ],
        [
            'name' => 'Deal 2',
            'stage' => 'qualification',
            'amount' => 50000,
            'probability' => 50,
            'expected_close_date' => now()->addDays(45),
        ],
        [
            'name' => 'Deal 3',
            'stage' => 'proposal',
            'amount' => 75000,
            'probability' => 75,
            'expected_close_date' => now()->addDays(15),
        ],
        [
            'name' => 'Deal 4',
            'stage' => 'negotiation',
            'amount' => 200000,
            'probability' => 90,
            'expected_close_date' => now()->addDays(60),
        ],
    ];

    $createdOpportunities = [];
    foreach ($opportunities as $oppData) {
        $opp = Opportunity::factory()->create(array_merge($oppData, [
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]));
        $createdOpportunities[] = $opp;
    }

    // Get forecast data via API
    $response = $this->getJson('/api/opportunities/forecast');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'total_pipeline_value',
        'weighted_pipeline_value',
        'forecast_by_stage',
        'forecast_by_month',
        'win_probability_average',
        'deals_count',
    ]);

    $forecastData = $response->json();

    // Verify total pipeline value
    $expectedTotalValue = array_sum(array_column($opportunities, 'amount'));
    expect($forecastData['total_pipeline_value'])->toBe($expectedTotalValue);

    // Verify weighted pipeline value
    $expectedWeightedValue = 0;
    foreach ($opportunities as $opp) {
        $expectedWeightedValue += ($opp['amount'] * $opp['probability'] / 100);
    }
    expect($forecastData['weighted_pipeline_value'])->toBe($expectedWeightedValue);

    // Verify forecast by stage
    expect($forecastData['forecast_by_stage'])->toHaveCount(4);
    
    foreach ($forecastData['forecast_by_stage'] as $stageData) {
        expect($stageData)->toHaveKeys(['stage', 'count', 'total_value', 'weighted_value']);
    }

    // Verify deals count
    expect($forecastData['deals_count'])->toBe(count($opportunities));
});

test('opportunity forecast groups by time periods correctly', function (): void {
    // Create opportunities with different close dates
    $thisMonth = now()->startOfMonth();
    $nextMonth = now()->addMonth()->startOfMonth();
    $futureMonth = now()->addMonths(3)->startOfMonth();

    $opportunities = [
        // This month
        Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'amount' => 50000,
            'probability' => 50,
            'expected_close_date' => $thisMonth->addDays(15),
        ]),
        Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'amount' => 30000,
            'probability' => 75,
            'expected_close_date' => $thisMonth->addDays(25),
        ]),
        // Next month
        Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'amount' => 100000,
            'probability' => 25,
            'expected_close_date' => $nextMonth->addDays(10),
        ]),
        // Future month
        Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'amount' => 75000,
            'probability' => 90,
            'expected_close_date' => $futureMonth->addDays(5),
        ]),
    ];

    $response = $this->getJson('/api/opportunities/forecast');
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Verify monthly forecast grouping
    expect($forecastData['forecast_by_month'])->not->toBeEmpty();
    
    $monthlyData = collect($forecastData['forecast_by_month']);
    
    // Should have at least 3 months represented
    expect($monthlyData->count())->toBeGreaterThanOrEqual(3);
    
    // Verify structure of monthly data
    foreach ($monthlyData as $monthData) {
        expect($monthData)->toHaveKeys(['month', 'year', 'count', 'total_value', 'weighted_value']);
    }
    
    // Verify this month's data
    $thisMonthData = $monthlyData->where('month', $thisMonth->month)
        ->where('year', $thisMonth->year)
        ->first();
    
    expect($thisMonthData)->not->toBeNull();
    expect($thisMonthData['count'])->toBe(2);
    expect($thisMonthData['total_value'])->toBe(80000); // 50000 + 30000
    expect($thisMonthData['weighted_value'])->toBe(47500); // (50000*0.5) + (30000*0.75)
});

test('opportunity forecast filters by team correctly', function (): void {
    // Create opportunities in current team
    $teamOpportunities = Opportunity::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'amount' => 50000,
        'probability' => 50,
    ]);

    // Create opportunities in different team
    $otherTeam = Team::factory()->create();
    $otherTeamOpportunities = Opportunity::factory()->count(2)->create([
        'team_id' => $otherTeam->id,
        'amount' => 100000,
        'probability' => 75,
    ]);

    $response = $this->getJson('/api/opportunities/forecast');
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Should only include current team's opportunities
    expect($forecastData['deals_count'])->toBe(3);
    expect($forecastData['total_pipeline_value'])->toBe(150000); // 3 * 50000
    expect($forecastData['weighted_pipeline_value'])->toBe(75000); // 3 * (50000 * 0.5)
});

test('opportunity forecast handles different forecast categories', function (): void {
    // Create opportunities with different forecast categories
    $opportunities = [
        Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'amount' => 100000,
            'probability' => 90,
            'forecast_category' => 'commit',
        ]),
        Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'amount' => 75000,
            'probability' => 60,
            'forecast_category' => 'best_case',
        ]),
        Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'amount' => 50000,
            'probability' => 25,
            'forecast_category' => 'pipeline',
        ]),
    ];

    $response = $this->getJson('/api/opportunities/forecast?include_categories=true');
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Verify forecast by category is included
    expect($forecastData)->toHaveKey('forecast_by_category');
    expect($forecastData['forecast_by_category'])->toHaveCount(3);
    
    $categoryData = collect($forecastData['forecast_by_category']);
    
    // Verify commit category
    $commitData = $categoryData->where('category', 'commit')->first();
    expect($commitData)->not->toBeNull();
    expect($commitData['count'])->toBe(1);
    expect($commitData['total_value'])->toBe(100000);
    expect($commitData['weighted_value'])->toBe(90000);
    
    // Verify best_case category
    $bestCaseData = $categoryData->where('category', 'best_case')->first();
    expect($bestCaseData)->not->toBeNull();
    expect($bestCaseData['count'])->toBe(1);
    expect($bestCaseData['total_value'])->toBe(75000);
    expect($bestCaseData['weighted_value'])->toBe(45000);
});

test('opportunity forecast excludes closed opportunities by default', function (): void {
    // Create open opportunities
    $openOpportunities = Opportunity::factory()->count(2)->create([
        'team_id' => $this->team->id,
        'amount' => 50000,
        'probability' => 50,
        'stage' => 'prospecting',
    ]);

    // Create closed won opportunity
    $closedWonOpp = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 100000,
        'probability' => 100,
        'stage' => 'closed_won',
        'closed_at' => now()->subDays(5),
    ]);

    // Create closed lost opportunity
    $closedLostOpp = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 75000,
        'probability' => 0,
        'stage' => 'closed_lost',
        'closed_at' => now()->subDays(3),
    ]);

    $response = $this->getJson('/api/opportunities/forecast');
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Should only include open opportunities
    expect($forecastData['deals_count'])->toBe(2);
    expect($forecastData['total_pipeline_value'])->toBe(100000); // 2 * 50000
});

test('opportunity forecast includes closed opportunities when requested', function (): void {
    // Create opportunities with different stages
    $openOpp = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 50000,
        'probability' => 50,
        'stage' => 'prospecting',
    ]);

    $closedWonOpp = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 100000,
        'probability' => 100,
        'stage' => 'closed_won',
        'closed_at' => now()->subDays(5),
    ]);

    $response = $this->getJson('/api/opportunities/forecast?include_closed=true');
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Should include both open and closed opportunities
    expect($forecastData['deals_count'])->toBe(2);
    expect($forecastData['total_pipeline_value'])->toBe(150000); // 50000 + 100000
});

test('opportunity forecast calculates win rate correctly', function (): void {
    // Create historical opportunities for win rate calculation
    $wonOpportunities = Opportunity::factory()->count(7)->create([
        'team_id' => $this->team->id,
        'stage' => 'closed_won',
        'closed_at' => now()->subDays(rand(1, 30)),
        'amount' => 50000,
    ]);

    $lostOpportunities = Opportunity::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'stage' => 'closed_lost',
        'closed_at' => now()->subDays(rand(1, 30)),
        'amount' => 25000,
    ]);

    $response = $this->getJson('/api/opportunities/forecast?include_win_rate=true');
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Verify win rate calculation
    expect($forecastData)->toHaveKey('historical_win_rate');
    expect($forecastData['historical_win_rate'])->toBe(70.0); // 7 won / (7 won + 3 lost) = 70%
    
    expect($forecastData)->toHaveKey('historical_deals_count');
    expect($forecastData['historical_deals_count'])->toBe(10);
});

test('opportunity forecast handles date range filtering', function (): void {
    // Create opportunities with different close dates
    $pastOpp = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 25000,
        'expected_close_date' => now()->subDays(30),
    ]);

    $currentOpp = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 50000,
        'expected_close_date' => now()->addDays(15),
    ]);

    $futureOpp = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 75000,
        'expected_close_date' => now()->addDays(90),
    ]);

    // Filter to next 60 days
    $startDate = now()->format('Y-m-d');
    $endDate = now()->addDays(60)->format('Y-m-d');
    
    $response = $this->getJson("/api/opportunities/forecast?start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Should only include opportunities within date range
    expect($forecastData['deals_count'])->toBe(1); // Only currentOpp
    expect($forecastData['total_pipeline_value'])->toBe(50000);
});

test('opportunity forecast updates in real-time when opportunities change', function (): void {
    // Create initial opportunity
    $opportunity = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'amount' => 100000,
        'probability' => 50,
    ]);

    // Get initial forecast
    $response1 = $this->getJson('/api/opportunities/forecast');
    $response1->assertStatus(200);
    $initialForecast = $response1->json();

    expect($initialForecast['total_pipeline_value'])->toBe(100000);
    expect($initialForecast['weighted_pipeline_value'])->toBe(50000);

    // Update opportunity
    $opportunity->update([
        'amount' => 150000,
        'probability' => 75,
    ]);

    // Get updated forecast
    $response2 = $this->getJson('/api/opportunities/forecast');
    $response2->assertStatus(200);
    $updatedForecast = $response2->json();

    // Verify forecast reflects changes
    expect($updatedForecast['total_pipeline_value'])->toBe(150000);
    expect($updatedForecast['weighted_pipeline_value'])->toBe(112500); // 150000 * 0.75
});

test('opportunity forecast handles empty pipeline gracefully', function (): void {
    // No opportunities created
    
    $response = $this->getJson('/api/opportunities/forecast');
    $response->assertStatus(200);

    $forecastData = $response->json();
    
    // Should return zero values
    expect($forecastData['total_pipeline_value'])->toBe(0);
    expect($forecastData['weighted_pipeline_value'])->toBe(0);
    expect($forecastData['deals_count'])->toBe(0);
    expect($forecastData['forecast_by_stage'])->toBeEmpty();
    expect($forecastData['forecast_by_month'])->toBeEmpty();
});

test('opportunity forecast caches results for performance', function (): void {
    // Create opportunities
    Opportunity::factory()->count(5)->create([
        'team_id' => $this->team->id,
        'amount' => 50000,
        'probability' => 50,
    ]);

    // First request
    $start1 = microtime(true);
    $response1 = $this->getJson('/api/opportunities/forecast');
    $time1 = microtime(true) - $start1;
    
    $response1->assertStatus(200);

    // Second request (should be cached)
    $start2 = microtime(true);
    $response2 = $this->getJson('/api/opportunities/forecast');
    $time2 = microtime(true) - $start2;
    
    $response2->assertStatus(200);

    // Results should be identical
    expect($response1->json())->toBe($response2->json());
    
    // Second request should be faster (cached)
    expect($time2)->toBeLessThan($time1);
});