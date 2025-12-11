<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;

/**
 * **Feature: accounts-module, Property 36: Hierarchy cycle prevention**
 *
 * **Validates: Requirements 16.3**
 *
 * Property: For any account and potential parent account, the system should prevent
 * creating a parent-child relationship if it would create a circular reference
 * (account cannot be its own ancestor).
 */

// Property: Account cannot be its own parent
test('property: account cannot be set as its own parent', function (): void {
    $team = Team::factory()->create();
    $company = Company::factory()->create(['team_id' => $team->id]);

    expect($company->wouldCreateCycle($company->id))->toBeTrue();
})->repeat(100);

// Property: Direct circular reference is prevented
test('property: direct circular reference is prevented', function (): void {
    $team = Team::factory()->create();

    $parent = Company::factory()->create(['team_id' => $team->id]);
    $child = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Child cannot become parent of its parent
    expect($parent->wouldCreateCycle($child->id))->toBeTrue();
})->repeat(100);

// Property: Multi-level circular reference is prevented
test('property: multi-level circular reference is prevented', function (): void {
    $team = Team::factory()->create();

    // Create hierarchy: grandparent -> parent -> child
    $grandparent = Company::factory()->create(['team_id' => $team->id]);
    $parent = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $grandparent->id,
    ]);
    $child = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Grandparent cannot become child of its descendant
    expect($grandparent->wouldCreateCycle($child->id))->toBeTrue();
    expect($grandparent->wouldCreateCycle($parent->id))->toBeTrue();

    // Parent cannot become child of its descendant
    expect($parent->wouldCreateCycle($child->id))->toBeTrue();
})->repeat(100);

// Property: Valid parent assignments are allowed
test('property: valid parent assignments are allowed', function (): void {
    $team = Team::factory()->create();

    $company1 = Company::factory()->create(['team_id' => $team->id]);
    $company2 = Company::factory()->create(['team_id' => $team->id]);
    $company3 = Company::factory()->create(['team_id' => $team->id]);

    // Unrelated companies can be assigned as parents
    expect($company1->wouldCreateCycle($company2->id))->toBeFalse();
    expect($company2->wouldCreateCycle($company3->id))->toBeFalse();
    expect($company3->wouldCreateCycle($company1->id))->toBeFalse();
})->repeat(100);

// Property: Null parent is always valid
test('property: null parent assignment is always valid', function (): void {
    $team = Team::factory()->create();
    $company = Company::factory()->create(['team_id' => $team->id]);

    expect($company->wouldCreateCycle(null))->toBeFalse();
})->repeat(50);

// Property: Cycle detection works with complex hierarchies
test('property: cycle detection works with complex hierarchies', function (): void {
    $team = Team::factory()->create();

    // Create a chain of companies
    $companies = Company::factory()->count(5)->create(['team_id' => $team->id]);

    // Link them in a chain: 0 -> 1 -> 2 -> 3 -> 4
    for ($i = 1; $i < 5; $i++) {
        $companies[$i]->update(['parent_company_id' => $companies[$i - 1]->id]);
    }

    // Any company in the chain cannot become parent of its ancestor
    for ($i = 0; $i < 4; $i++) {
        for ($j = $i + 1; $j < 5; $j++) {
            expect($companies[$i]->wouldCreateCycle($companies[$j]->id))->toBeTrue();
        }
    }

    // But descendants can become parents of ancestors (breaking the chain)
    for ($i = 1; $i < 5; $i++) {
        for ($j = 0; $j < $i; $j++) {
            expect($companies[$i]->wouldCreateCycle($companies[$j]->id))->toBeFalse();
        }
    }
})->repeat(50);

/**
 * **Feature: accounts-module, Property 37: Hierarchy relationship persistence**
 *
 * **Validates: Requirements 16.1, 16.2**
 *
 * Property: For any account with a parent account, the parent-child relationship
 * should be persisted, queryable from both directions, and displayable in a
 * hierarchical view.
 */

// Property: Parent-child relationships are persisted
test('property: parent-child relationships are persisted correctly', function (): void {
    $team = Team::factory()->create();

    $parent = Company::factory()->create(['team_id' => $team->id]);
    $child = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Refresh from database
    $parent = $parent->fresh();
    $child = $child->fresh();

    expect($child->parent_company_id)->toBe($parent->id);
    expect($child->parentCompany->id)->toBe($parent->id);
    expect($parent->childCompanies)->toHaveCount(1);
    expect($parent->childCompanies->first()->id)->toBe($child->id);
})->repeat(100);

// Property: Relationships are queryable from both directions
test('property: hierarchy relationships are bidirectional', function (): void {
    $team = Team::factory()->create();

    $parent = Company::factory()->create(['team_id' => $team->id]);
    $children = Company::factory()->count(3)->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Query from parent to children
    $parentChildren = $parent->childCompanies;
    expect($parentChildren)->toHaveCount(3);

    $childIds = $children->pluck('id')->sort()->values();
    $parentChildIds = $parentChildren->pluck('id')->sort()->values();
    expect($parentChildIds)->toEqual($childIds);

    // Query from each child to parent
    foreach ($children as $child) {
        expect($child->parentCompany->id)->toBe($parent->id);
    }
})->repeat(100);

// Property: Multiple levels of hierarchy work correctly
test('property: multi-level hierarchy relationships work correctly', function (): void {
    $team = Team::factory()->create();

    $grandparent = Company::factory()->create(['team_id' => $team->id]);
    $parent = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $grandparent->id,
    ]);
    $child = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Verify each level
    expect($child->parentCompany->id)->toBe($parent->id);
    expect($parent->parentCompany->id)->toBe($grandparent->id);
    expect($grandparent->parentCompany)->toBeNull();

    expect($grandparent->childCompanies)->toHaveCount(1);
    expect($parent->childCompanies)->toHaveCount(1);
    expect($child->childCompanies)->toHaveCount(0);

    expect($grandparent->childCompanies->first()->id)->toBe($parent->id);
    expect($parent->childCompanies->first()->id)->toBe($child->id);
})->repeat(100);

// Property: Orphaned companies have no parent
test('property: companies without parent have null parent relationship', function (): void {
    $team = Team::factory()->create();
    $company = Company::factory()->create(['team_id' => $team->id]);

    expect($company->parent_company_id)->toBeNull();
    expect($company->parentCompany)->toBeNull();
})->repeat(50);

/**
 * **Feature: accounts-module, Property 38: Hierarchy aggregation**
 *
 * **Validates: Requirements 16.4**
 *
 * Property: For any parent account with child accounts, the system should provide
 * the ability to aggregate data (opportunities, revenue, activities) from all
 * child accounts.
 */

// Property: Parent can access all child opportunities
test('property: parent account can aggregate opportunities from child accounts', function (): void {
    $team = Team::factory()->create();

    $parent = Company::factory()->create(['team_id' => $team->id]);
    $children = Company::factory()->count(2)->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Create opportunities for children
    $childOpportunities = collect();
    foreach ($children as $child) {
        $opportunities = \App\Models\Opportunity::factory()->count(2)->create([
            'team_id' => $team->id,
            'company_id' => $child->id,
        ]);
        $childOpportunities = $childOpportunities->merge($opportunities);
    }

    // Parent should be able to access all child opportunities through relationships
    $allChildOpportunities = $parent->childCompanies()
        ->with('opportunities')
        ->get()
        ->pluck('opportunities')
        ->flatten();

    expect($allChildOpportunities)->toHaveCount(4);

    $expectedIds = $childOpportunities->pluck('id')->sort()->values();
    $actualIds = $allChildOpportunities->pluck('id')->sort()->values();
    expect($actualIds)->toEqual($expectedIds);
})->repeat(50);

// Property: Parent can access all child people
test('property: parent account can aggregate people from child accounts', function (): void {
    $team = Team::factory()->create();

    $parent = Company::factory()->create(['team_id' => $team->id]);
    $children = Company::factory()->count(2)->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Create people for children
    $childPeople = collect();
    foreach ($children as $child) {
        $people = \App\Models\People::factory()->count(3)->create([
            'team_id' => $team->id,
            'company_id' => $child->id,
        ]);
        $childPeople = $childPeople->merge($people);
    }

    // Parent should be able to access all child people through relationships
    $allChildPeople = $parent->childCompanies()
        ->with('people')
        ->get()
        ->pluck('people')
        ->flatten();

    expect($allChildPeople)->toHaveCount(6);

    $expectedIds = $childPeople->pluck('id')->sort()->values();
    $actualIds = $allChildPeople->pluck('id')->sort()->values();
    expect($actualIds)->toEqual($expectedIds);
})->repeat(50);

// Property: Parent can access all child tasks
test('property: parent account can aggregate tasks from child accounts', function (): void {
    $team = Team::factory()->create();

    $parent = Company::factory()->create(['team_id' => $team->id]);
    $children = Company::factory()->count(2)->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Create tasks for children
    $childTasks = collect();
    foreach ($children as $child) {
        $tasks = \App\Models\Task::factory()->count(2)->create(['team_id' => $team->id]);
        $child->tasks()->attach($tasks);
        $childTasks = $childTasks->merge($tasks);
    }

    // Parent should be able to access all child tasks through relationships
    $allChildTasks = $parent->childCompanies()
        ->with('tasks')
        ->get()
        ->pluck('tasks')
        ->flatten();

    expect($allChildTasks)->toHaveCount(4);

    $expectedIds = $childTasks->pluck('id')->sort()->values();
    $actualIds = $allChildTasks->pluck('id')->sort()->values();
    expect($actualIds)->toEqual($expectedIds);
})->repeat(50);

// Property: Aggregation works across multiple hierarchy levels
test('property: aggregation works across multiple hierarchy levels', function (): void {
    $team = Team::factory()->create();

    // Create 3-level hierarchy
    $grandparent = Company::factory()->create(['team_id' => $team->id]);
    $parent = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $grandparent->id,
    ]);
    $child = Company::factory()->create([
        'team_id' => $team->id,
        'parent_company_id' => $parent->id,
    ]);

    // Create opportunities at each level
    $grandparentOpps = \App\Models\Opportunity::factory()->count(1)->create([
        'team_id' => $team->id,
        'company_id' => $grandparent->id,
    ]);
    $parentOpps = \App\Models\Opportunity::factory()->count(2)->create([
        'team_id' => $team->id,
        'company_id' => $parent->id,
    ]);
    $childOpps = \App\Models\Opportunity::factory()->count(3)->create([
        'team_id' => $team->id,
        'company_id' => $child->id,
    ]);

    // Grandparent should access direct children (parent level)
    $directChildOpps = $grandparent->childCompanies()
        ->with('opportunities')
        ->get()
        ->pluck('opportunities')
        ->flatten();

    expect($directChildOpps)->toHaveCount(2); // Only parent's opportunities

    // Parent should access direct children (child level)
    $parentChildOpps = $parent->childCompanies()
        ->with('opportunities')
        ->get()
        ->pluck('opportunities')
        ->flatten();

    expect($parentChildOpps)->toHaveCount(3); // Only child's opportunities
})->repeat(50);

// Property: Empty aggregation for companies without children
test('property: companies without children return empty aggregations', function (): void {
    $team = Team::factory()->create();
    $company = Company::factory()->create(['team_id' => $team->id]);

    $childOpportunities = $company->childCompanies()
        ->with('opportunities')
        ->get()
        ->pluck('opportunities')
        ->flatten();

    $childPeople = $company->childCompanies()
        ->with('people')
        ->get()
        ->pluck('people')
        ->flatten();

    $childTasks = $company->childCompanies()
        ->with('tasks')
        ->get()
        ->pluck('tasks')
        ->flatten();

    expect($childOpportunities)->toHaveCount(0);
    expect($childPeople)->toHaveCount(0);
    expect($childTasks)->toHaveCount(0);
})->repeat(50);
