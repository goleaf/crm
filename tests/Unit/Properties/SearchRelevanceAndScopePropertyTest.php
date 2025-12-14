<?php

declare(strict_types=1);

namespace Tests\Unit\Properties;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SearchHistory;
use App\Models\SearchIndex;
use App\Models\Team;
use App\Models\User;
use App\Services\Search\AdvancedSearchService;
use App\Services\Search\SearchIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PropertyTestCase;

/**
 * **Feature: data-management, Property 6: Search relevance and scope**
 *
 * Property: Search results honor filters, operators, and permissions; ranking surfaces most relevant results across modules.
 */
final class SearchRelevanceAndScopePropertyTest extends PropertyTestCase
{
    public $generators;
    use RefreshDatabase;

    private AdvancedSearchService $advancedSearch;

    private SearchIndexService $searchIndex;

    protected function setUp(): void
    {
        parent::setUp();
        $this->advancedSearch = resolve(AdvancedSearchService::class);
        $this->searchIndex = resolve(SearchIndexService::class);
    }

    public function test_search_results_respect_team_scoping(): void
    {
        $this->forAll(
            $this->generators->team(),
            $this->generators->team(),
            $this->generators->nonEmptyString(1, 50),
        )->then(function (Team $team1, Team $team2, string $searchTerm): void {
            // Create companies in different teams
            $company1 = Company::factory()->create([
                'team_id' => $team1->id,
                'name' => "Test {$searchTerm} Company 1",
            ]);

            $company2 = Company::factory()->create([
                'team_id' => $team2->id,
                'name' => "Test {$searchTerm} Company 2",
            ]);

            // Index both companies
            $this->searchIndex->indexModel($company1, 'companies', ['name']);
            $this->searchIndex->indexModel($company2, 'companies', ['name']);

            // Search from team1 perspective
            $this->actingAs(User::factory()->create(['current_team_id' => $team1->id]));

            $results = $this->advancedSearch->search(
                query: $searchTerm,
                module: 'companies',
            );

            // Should only return results from team1
            $resultIds = $results->pluck('id')->toArray();

            $this->assertContains($company1->id, $resultIds, 'Team 1 company should be in results');
            $this->assertNotContains($company2->id, $resultIds, 'Team 2 company should not be in results');
        });
    }

    public function test_search_filters_are_applied_correctly(): void
    {
        $this->forAll(
            $this->generators->team(),
            $this->generators->nonEmptyString(1, 50),
            $this->generators->elements(['active', 'inactive']),
        )->then(function (Team $team, string $searchTerm, string $status): void {
            // Create companies with different statuses
            $activeCompany = Company::factory()->create([
                'team_id' => $team->id,
                'name' => "Active {$searchTerm} Company",
                'status' => 'active',
            ]);

            $inactiveCompany = Company::factory()->create([
                'team_id' => $team->id,
                'name' => "Inactive {$searchTerm} Company",
                'status' => 'inactive',
            ]);

            // Index both companies
            $this->searchIndex->indexModel($activeCompany, 'companies', ['name']);
            $this->searchIndex->indexModel($inactiveCompany, 'companies', ['name']);

            $this->actingAs(User::factory()->create(['current_team_id' => $team->id]));

            // Search with status filter
            $results = $this->advancedSearch->search(
                query: $searchTerm,
                module: 'companies',
                filters: [
                    ['field' => 'status', 'operator' => 'equals', 'value' => $status],
                ],
            );

            $resultIds = $results->pluck('id')->toArray();

            if ($status === 'active') {
                $this->assertContains($activeCompany->id, $resultIds, 'Active company should be in filtered results');
                $this->assertNotContains($inactiveCompany->id, $resultIds, 'Inactive company should not be in active filter results');
            } else {
                $this->assertContains($inactiveCompany->id, $resultIds, 'Inactive company should be in filtered results');
                $this->assertNotContains($activeCompany->id, $resultIds, 'Active company should not be in inactive filter results');
            }
        });
    }

    public function test_search_operators_work_correctly(): void
    {
        $this->forAll(
            $this->generators->team(),
            $this->generators->nonEmptyString(5, 20),
        )->then(function (Team $team, string $baseName): void {
            // Create companies with predictable names for operator testing
            $company1 = Company::factory()->create([
                'team_id' => $team->id,
                'name' => "Start {$baseName} End",
            ]);

            $company2 = Company::factory()->create([
                'team_id' => $team->id,
                'name' => 'Different Name',
            ]);

            $this->actingAs(User::factory()->create(['current_team_id' => $team->id]));

            // Test 'contains' operator
            $results = $this->advancedSearch->search(
                query: '',
                module: 'companies',
                filters: [
                    ['field' => 'name', 'operator' => 'contains', 'value' => $baseName],
                ],
            );

            $resultIds = $results->pluck('id')->toArray();
            $this->assertContains($company1->id, $resultIds, 'Company containing search term should be found');
            $this->assertNotContains($company2->id, $resultIds, 'Company not containing search term should not be found');

            // Test 'starts_with' operator
            $results = $this->advancedSearch->search(
                query: '',
                module: 'companies',
                filters: [
                    ['field' => 'name', 'operator' => 'starts_with', 'value' => 'Start'],
                ],
            );

            $resultIds = $results->pluck('id')->toArray();
            $this->assertContains($company1->id, $resultIds, 'Company starting with term should be found');
            $this->assertNotContains($company2->id, $resultIds, 'Company not starting with term should not be found');
        });
    }

    public function test_search_ranking_prioritizes_relevant_results(): void
    {
        $this->forAll(
            $this->generators->team(),
            $this->generators->nonEmptyString(3, 20),
        )->then(function (Team $team, string $searchTerm): void {
            // Create companies with different relevance levels
            $exactMatch = Company::factory()->create([
                'team_id' => $team->id,
                'name' => $searchTerm,
            ]);

            $partialMatch = Company::factory()->create([
                'team_id' => $team->id,
                'name' => "Some {$searchTerm} Company",
            ]);

            $weakMatch = Company::factory()->create([
                'team_id' => $team->id,
                'name' => "Company with {$searchTerm} somewhere",
            ]);

            // Index all companies
            $this->searchIndex->indexModel($exactMatch, 'companies', ['name']);
            $this->searchIndex->indexModel($partialMatch, 'companies', ['name']);
            $this->searchIndex->indexModel($weakMatch, 'companies', ['name']);

            // Simulate different search frequencies to affect ranking
            $exactIndex = SearchIndex::where('searchable_id', $exactMatch->id)->first();
            $exactIndex?->update(['search_count' => 10, 'ranking_score' => 9.0]);

            $partialIndex = SearchIndex::where('searchable_id', $partialMatch->id)->first();
            $partialIndex?->update(['search_count' => 5, 'ranking_score' => 5.0]);

            $weakIndex = SearchIndex::where('searchable_id', $weakMatch->id)->first();
            $weakIndex?->update(['search_count' => 1, 'ranking_score' => 1.0]);

            $this->actingAs(User::factory()->create(['current_team_id' => $team->id]));

            $results = $this->advancedSearch->search(
                query: $searchTerm,
                module: 'companies',
                options: ['sort' => 'relevance'],
            );

            // Results should be ordered by relevance (exact match first)
            $resultIds = $results->pluck('id')->toArray();

            $this->assertNotEmpty($resultIds, 'Search should return results');

            // The exact match should appear before partial matches in relevance-sorted results
            $exactPosition = array_search($exactMatch->id, $resultIds);
            $partialPosition = array_search($partialMatch->id, $resultIds);

            if ($exactPosition !== false && $partialPosition !== false) {
                $this->assertLessThan($partialPosition, $exactPosition, 'Exact match should rank higher than partial match');
            }
        });
    }

    public function test_cross_module_search_returns_diverse_results(): void
    {
        $this->forAll(
            $this->generators->team(),
            $this->generators->nonEmptyString(3, 20),
        )->then(function (Team $team, string $searchTerm): void {
            // Create records in different modules with the same search term
            $company = Company::factory()->create([
                'team_id' => $team->id,
                'name' => "{$searchTerm} Company",
            ]);

            $person = People::factory()->create([
                'team_id' => $team->id,
                'name' => "{$searchTerm} Person",
            ]);

            $opportunity = Opportunity::factory()->create([
                'team_id' => $team->id,
                'name' => "{$searchTerm} Opportunity",
            ]);

            // Index all records
            $this->searchIndex->indexModel($company, 'companies', ['name']);
            $this->searchIndex->indexModel($person, 'people', ['name']);
            $this->searchIndex->indexModel($opportunity, 'opportunities', ['name']);

            $this->actingAs(User::factory()->create(['current_team_id' => $team->id]));

            // Search across all modules
            $results = $this->advancedSearch->search(
                query: $searchTerm, // Search all modules
            );

            $resultTypes = $results->pluck('search_module')->unique()->toArray();

            // Should return results from multiple modules
            $this->assertGreaterThanOrEqual(1, count($resultTypes), 'Cross-module search should return results from at least one module');

            // Verify we get the expected records
            $resultIds = $results->pluck('id')->toArray();
            $this->assertContains($company->id, $resultIds, 'Company should be in cross-module results');
            $this->assertContains($person->id, $resultIds, 'Person should be in cross-module results');
            $this->assertContains($opportunity->id, $resultIds, 'Opportunity should be in cross-module results');
        });
    }

    public function test_search_history_is_recorded_correctly(): void
    {
        $this->forAll(
            $this->generators->team(),
            $this->generators->user(),
            $this->generators->nonEmptyString(3, 50),
        )->then(function (Team $team, User $user, string $query): void {
            $user->update(['current_team_id' => $team->id]);
            $this->actingAs($user);

            $initialHistoryCount = SearchHistory::where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->count();

            // Perform a search
            $this->advancedSearch->search(
                query: $query,
                module: 'companies',
            );

            // Verify search history was recorded
            $finalHistoryCount = SearchHistory::where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->count();

            $this->assertEquals($initialHistoryCount + 1, $finalHistoryCount, 'Search history should be recorded');

            // Verify the recorded search details
            $latestSearch = SearchHistory::where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->latest('searched_at')
                ->first();

            $this->assertNotNull($latestSearch, 'Latest search should exist');
            $this->assertEquals($query, $latestSearch->query, 'Search query should be recorded correctly');
            $this->assertEquals('companies', $latestSearch->module, 'Search module should be recorded correctly');
            $this->assertIsNumeric($latestSearch->execution_time, 'Execution time should be recorded');
            $this->assertGreaterThanOrEqual(0, $latestSearch->execution_time, 'Execution time should be non-negative');
        });
    }

    public function test_search_suggestions_are_relevant(): void
    {
        $this->forAll(
            $this->generators->team(),
            $this->generators->nonEmptyString(3, 20),
        )->then(function (Team $team, string $baseTerm): void {
            // Create search indices with terms that should match
            SearchIndex::factory()->create([
                'team_id' => $team->id,
                'term' => $baseTerm,
                'module' => 'companies',
                'ranking_score' => 8.0,
                'search_count' => 10,
            ]);

            SearchIndex::factory()->create([
                'team_id' => $team->id,
                'term' => "{$baseTerm}ing",
                'module' => 'people',
                'ranking_score' => 6.0,
                'search_count' => 5,
            ]);

            SearchIndex::factory()->create([
                'team_id' => $team->id,
                'term' => 'unrelated term',
                'module' => 'companies',
                'ranking_score' => 2.0,
                'search_count' => 1,
            ]);

            $this->actingAs(User::factory()->create(['current_team_id' => $team->id]));

            // Get suggestions for partial term
            $suggestions = $this->advancedSearch->getSuggestions(
                query: substr($baseTerm, 0, -1), // Remove last character for partial match
                module: null,
                limit: 10,
            );

            $suggestionTerms = $suggestions->pluck('term')->toArray();

            // Should include relevant suggestions
            $this->assertContains($baseTerm, $suggestionTerms, 'Exact matching term should be suggested');
            $this->assertContains("{$baseTerm}ing", $suggestionTerms, 'Partial matching term should be suggested');
            $this->assertNotContains('unrelated term', $suggestionTerms, 'Unrelated term should not be suggested');
        });
    }
}
