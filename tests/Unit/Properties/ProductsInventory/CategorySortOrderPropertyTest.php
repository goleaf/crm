<?php

declare(strict_types=1);

use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Feature: products-inventory, Property 8: Category sort order persistence**
 *
 * **Validates: Requirements 2.5**
 *
 * Property: For any set of categories with assigned sort orders, reordering and persisting
 * should maintain the new sequence correctly.
 */

// Property: Category sort order is persisted and maintained correctly
test('property: category sort order persistence maintains correct sequence', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create a parent category
    $parent = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Parent Category',
        'sort_order' => 1,
    ]);

    // Create random number of child categories with random sort orders
    $categoryCount = fake()->numberBetween(3, 8);
    $categories = [];
    $sortOrders = range(1, $categoryCount);
    shuffle($sortOrders); // Randomize the sort orders

    for ($i = 0; $i < $categoryCount; $i++) {
        $category = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => "Category {$i}",
            'sort_order' => $sortOrders[$i],
        ]);
        $categories[] = $category;
    }

    // Retrieve children and verify they are ordered by sort_order
    $parent->refresh();
    $orderedChildren = $parent->children;

    expect($orderedChildren)->toHaveCount($categoryCount);

    // Verify categories are returned in sort_order sequence
    $previousSortOrder = 0;
    foreach ($orderedChildren as $child) {
        expect($child->sort_order)->toBeGreaterThan($previousSortOrder);
        $previousSortOrder = $child->sort_order;
    }

    // Verify the sort orders match the expected sequence
    $expectedSortedOrders = collect($sortOrders)->sort()->values();
    $actualSortOrders = $orderedChildren->pluck('sort_order');

    expect($actualSortOrders->toArray())->toBe($expectedSortedOrders->all());

    // Test reordering: Update sort orders to new sequence
    $newSortOrders = range(1, $categoryCount);
    shuffle($newSortOrders);

    foreach ($categories as $index => $category) {
        $category->update(['sort_order' => $newSortOrders[$index]]);
    }

    // Verify the new order is persisted
    $parent->refresh();
    $reorderedChildren = $parent->children;

    $newExpectedSortedOrders = collect($newSortOrders)->sort()->values();
    $newActualSortOrders = $reorderedChildren->pluck('sort_order');

    expect($newActualSortOrders->toArray())->toBe($newExpectedSortedOrders->all());

    // Verify categories are still returned in correct order
    $previousSortOrder = 0;
    foreach ($reorderedChildren as $child) {
        expect($child->sort_order)->toBeGreaterThan($previousSortOrder);
        $previousSortOrder = $child->sort_order;
    }
})->repeat(100);

// Property: Category sort order works correctly across different hierarchy levels
test('property: category sort order works independently at each hierarchy level', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create root categories with different sort orders
    $rootCount = fake()->numberBetween(2, 4);
    $rootCategories = [];
    $rootSortOrders = range(1, $rootCount);
    shuffle($rootSortOrders);

    for ($i = 0; $i < $rootCount; $i++) {
        $rootCategory = ProductCategory::create([
            'team_id' => $team->id,
            'name' => "Root Category {$i}",
            'sort_order' => $rootSortOrders[$i],
        ]);
        $rootCategories[] = $rootCategory;
    }

    // For each root category, create child categories with their own sort orders
    $allChildCategories = [];
    foreach ($rootCategories as $rootIndex => $rootCategory) {
        $childCount = fake()->numberBetween(2, 4);
        $childSortOrders = range(1, $childCount);
        shuffle($childSortOrders);

        $childCategories = [];
        for ($j = 0; $j < $childCount; $j++) {
            $childCategory = ProductCategory::create([
                'team_id' => $team->id,
                'parent_id' => $rootCategory->id,
                'name' => "Child {$rootIndex}-{$j}",
                'sort_order' => $childSortOrders[$j],
            ]);
            $childCategories[] = $childCategory;
        }
        $allChildCategories[$rootIndex] = $childCategories;
    }

    // Verify root categories are ordered correctly
    $orderedRoots = ProductCategory::where('team_id', $team->id)
        ->whereNull('parent_id')
        ->ordered()
        ->get();

    expect($orderedRoots)->toHaveCount($rootCount);

    $previousRootSortOrder = 0;
    foreach ($orderedRoots as $root) {
        expect($root->sort_order)->toBeGreaterThan($previousRootSortOrder);
        $previousRootSortOrder = $root->sort_order;
    }

    // Verify each root's children are ordered correctly within their level
    foreach ($rootCategories as $rootCategory) {
        $rootCategory->refresh();
        $orderedChildren = $rootCategory->children;

        $previousChildSortOrder = 0;
        foreach ($orderedChildren as $child) {
            expect($child->sort_order)->toBeGreaterThan($previousChildSortOrder);
            $previousChildSortOrder = $child->sort_order;
        }

        // Verify the children belong to the correct parent
        foreach ($orderedChildren as $child) {
            expect($child->parent_id)->toBe($rootCategory->id);
        }
    }

    // Test that changing sort order in one level doesn't affect other levels
    $firstRoot = $rootCategories[0];
    $originalRootSortOrder = $firstRoot->sort_order;
    $firstRoot->update(['sort_order' => 999]);

    // Verify other roots are unaffected
    foreach ($rootCategories as $index => $rootCategory) {
        if ($index !== 0) {
            $rootCategory->refresh();
            expect($rootCategory->sort_order)->toBe($rootSortOrders[$index]);
        }
    }

    // Verify children of first root are unaffected
    $firstRoot->refresh();
    $firstRootChildren = $firstRoot->children;
    foreach ($firstRootChildren as $childIndex => $child) {
        expect($child->sort_order)->toBe($allChildCategories[0][$childIndex]->sort_order);
    }
})->repeat(100);

// Property: Category sort order handles duplicate values gracefully
test('property: category sort order handles duplicate values correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    $parent = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Parent Category',
        'sort_order' => 1,
    ]);

    // Create categories with some duplicate sort orders
    $categoryCount = fake()->numberBetween(4, 6);
    $categories = [];

    // Create some categories with duplicate sort orders
    $duplicateSortOrder = fake()->numberBetween(5, 10);
    $duplicateCount = fake()->numberBetween(2, 3);

    for ($i = 0; $i < $duplicateCount; $i++) {
        $category = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => "Duplicate Sort {$i}",
            'sort_order' => $duplicateSortOrder,
        ]);
        $categories[] = $category;
    }

    // Create categories with unique sort orders
    for ($i = $duplicateCount; $i < $categoryCount; $i++) {
        $uniqueSortOrder = $duplicateSortOrder + ($i - $duplicateCount + 1) * 5;
        $category = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => "Unique Sort {$i}",
            'sort_order' => $uniqueSortOrder,
        ]);
        $categories[] = $category;
    }

    // Verify categories are still returned in a consistent order
    $parent->refresh();
    $orderedChildren = $parent->children;

    expect($orderedChildren)->toHaveCount($categoryCount);

    // Categories with same sort_order should be ordered by name (secondary sort)
    $previousSortOrder = 0;
    $previousName = '';

    foreach ($orderedChildren as $child) {
        if ($child->sort_order === $previousSortOrder) {
            // When sort orders are equal, should be ordered by name
            expect($child->name)->toBeGreaterThan($previousName);
        } else {
            // Sort order should be greater than or equal to previous
            expect($child->sort_order)->toBeGreaterThanOrEqual($previousSortOrder);
        }

        $previousSortOrder = $child->sort_order;
        $previousName = $child->name;
    }

    // Verify all categories with duplicate sort order are grouped together
    $duplicateCategories = $orderedChildren->where('sort_order', $duplicateSortOrder);
    expect($duplicateCategories)->toHaveCount($duplicateCount);

    // Verify they are ordered by name within the group
    $duplicateNames = $duplicateCategories->pluck('name')->toArray();
    $sortedDuplicateNames = $duplicateCategories->pluck('name')->sort()->values()->toArray();
    expect($duplicateNames)->toBe($sortedDuplicateNames);
})->repeat(100);

// Property: Category sort order auto-assignment works correctly for new categories
test('property: category sort order auto-assignment maintains sequence', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    $parent = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Parent Category',
        'sort_order' => 1,
    ]);

    // Create some initial categories with explicit sort orders
    $initialCount = fake()->numberBetween(2, 4);
    $maxSortOrder = 0;

    for ($i = 0; $i < $initialCount; $i++) {
        $sortOrder = fake()->numberBetween($maxSortOrder + 1, $maxSortOrder + 5);
        $maxSortOrder = $sortOrder;

        ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => "Initial Category {$i}",
            'sort_order' => $sortOrder,
        ]);
    }

    // Create new categories without explicit sort_order (should auto-assign)
    $newCategoriesCount = fake()->numberBetween(2, 3);
    $newCategories = [];

    for ($i = 0; $i < $newCategoriesCount; $i++) {
        $category = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => "Auto Sort Category {$i}",
            // sort_order not specified - should auto-assign
        ]);
        $newCategories[] = $category;
    }

    // Verify auto-assigned sort orders are correct
    foreach ($newCategories as $index => $category) {
        $expectedSortOrder = $maxSortOrder + $index + 1;
        expect($category->sort_order)->toBe($expectedSortOrder);
    }

    // Verify all categories are still in correct order
    $parent->refresh();
    $allChildren = $parent->children;

    $previousSortOrder = 0;
    foreach ($allChildren as $child) {
        expect($child->sort_order)->toBeGreaterThan($previousSortOrder);
        $previousSortOrder = $child->sort_order;
    }

    // Test auto-assignment for root categories (no parent)
    $existingRootCount = ProductCategory::where('team_id', $team->id)
        ->whereNull('parent_id')
        ->count();

    $newRootCategory = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Auto Sort Root Category',
        // sort_order not specified - should auto-assign
    ]);

    // Should get the next available sort order for root level
    expect($newRootCategory->sort_order)->toBe($existingRootCount + 1);
})->repeat(100);

// Property: Category sort order respects team boundaries
test('property: category sort order respects team boundaries', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach([$team1->id, $team2->id]);
    $user->switchTeam($team1);

    $this->actingAs($user);

    // Create categories in team 1
    $team1Categories = [];
    for ($i = 0; $i < 3; $i++) {
        $category = ProductCategory::create([
            'team_id' => $team1->id,
            'name' => "Team 1 Category {$i}",
            'sort_order' => $i + 1,
        ]);
        $team1Categories[] = $category;
    }

    // Create categories in team 2 with overlapping sort orders
    $team2Categories = [];
    for ($i = 0; $i < 3; $i++) {
        $category = ProductCategory::create([
            'team_id' => $team2->id,
            'name' => "Team 2 Category {$i}",
            'sort_order' => $i + 1, // Same sort orders as team 1
        ]);
        $team2Categories[] = $category;
    }

    // Verify team 1 categories are ordered correctly within team 1
    $team1OrderedCategories = ProductCategory::where('team_id', $team1->id)
        ->whereNull('parent_id')
        ->ordered()
        ->get();

    expect($team1OrderedCategories)->toHaveCount(3);

    $previousSortOrder = 0;
    foreach ($team1OrderedCategories as $category) {
        expect($category->team_id)->toBe($team1->id);
        expect($category->sort_order)->toBeGreaterThan($previousSortOrder);
        $previousSortOrder = $category->sort_order;
    }

    // Verify team 2 categories are ordered correctly within team 2
    $team2OrderedCategories = ProductCategory::where('team_id', $team2->id)
        ->whereNull('parent_id')
        ->ordered()
        ->get();

    expect($team2OrderedCategories)->toHaveCount(3);

    $previousSortOrder = 0;
    foreach ($team2OrderedCategories as $category) {
        expect($category->team_id)->toBe($team2->id);
        expect($category->sort_order)->toBeGreaterThan($previousSortOrder);
        $previousSortOrder = $category->sort_order;
    }

    // Verify auto-assignment respects team boundaries
    $newTeam1Category = ProductCategory::create([
        'team_id' => $team1->id,
        'name' => 'New Team 1 Category',
        // sort_order not specified
    ]);

    $newTeam2Category = ProductCategory::create([
        'team_id' => $team2->id,
        'name' => 'New Team 2 Category',
        // sort_order not specified
    ]);

    // Both should get sort_order 4 (next in their respective teams)
    expect($newTeam1Category->sort_order)->toBe(4);
    expect($newTeam2Category->sort_order)->toBe(4);

    // Verify they don't interfere with each other
    expect($newTeam1Category->team_id)->toBe($team1->id);
    expect($newTeam2Category->team_id)->toBe($team2->id);
})->repeat(100);
