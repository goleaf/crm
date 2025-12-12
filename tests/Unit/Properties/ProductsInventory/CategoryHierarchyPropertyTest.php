<?php

declare(strict_types=1);

use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Feature: products-inventory, Property 6: Category hierarchy preservation**
 *
 * **Validates: Requirements 2.2**
 *
 * Property: For any category with parent-child relationships, the hierarchical structure
 * should be correctly represented and retrievable.
 */

// Property: Category hierarchy relationships are preserved correctly
test('property: category hierarchy preservation maintains parent-child relationships', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create a random hierarchy depth (1-5 levels)
    $depth = fake()->numberBetween(1, 5);
    $categories = [];
    
    // Create root category
    $rootCategory = ProductCategory::create([
        'team_id' => $team->id,
        'name' => fake()->words(2, true),
        'description' => fake()->optional()->sentence(),
        'sort_order' => fake()->numberBetween(1, 100),
    ]);
    $categories[] = $rootCategory;

    // Create nested categories
    $parent = $rootCategory;
    for ($i = 1; $i < $depth; $i++) {
        $child = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(1, 100),
        ]);
        $categories[] = $child;
        $parent = $child;
    }

    // Verify hierarchy structure is preserved
    $leafCategory = end($categories);
    $ancestors = $leafCategory->ancestors();
    
    // Check that ancestors are correctly retrieved
    expect($ancestors->count())->toBe($depth - 1);
    
    // Verify ancestor chain is correct
    for ($i = $depth - 2; $i >= 0; $i--) {
        expect($ancestors->contains('id', $categories[$i]->id))->toBeTrue();
    }

    // Verify parent-child relationships
    for ($i = 0; $i < $depth - 1; $i++) {
        $parent = $categories[$i];
        $child = $categories[$i + 1];
        
        expect($child->parent_id)->toBe($parent->id);
        expect($parent->children->contains('id', $child->id))->toBeTrue();
        expect($child->isDescendantOf($parent))->toBeTrue();
        expect($parent->isAncestorOf($child))->toBeTrue();
    }

    // Verify root category properties
    expect($rootCategory->parent_id)->toBeNull();
    expect($rootCategory->getDepth())->toBe(0);
    expect($rootCategory->getRoot()->id)->toBe($rootCategory->id);

    // Verify leaf category properties
    expect($leafCategory->getDepth())->toBe($depth - 1);
    expect($leafCategory->getRoot()->id)->toBe($rootCategory->id);
    expect($leafCategory->children)->toHaveCount(0);

    // Verify descendants from root
    $descendants = $rootCategory->descendants();
    expect($descendants->count())->toBe($depth - 1);
    
    for ($i = 1; $i < $depth; $i++) {
        expect($descendants->contains('id', $categories[$i]->id))->toBeTrue();
    }
})->repeat(100);

// Property: Category hierarchy with siblings maintains correct relationships
test('property: category hierarchy with siblings preserves sibling relationships', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create parent category
    $parent = ProductCategory::create([
        'team_id' => $team->id,
        'name' => fake()->words(2, true),
        'sort_order' => 1,
    ]);

    // Create random number of siblings (2-5)
    $siblingCount = fake()->numberBetween(2, 5);
    $siblings = [];
    
    for ($i = 0; $i < $siblingCount; $i++) {
        $sibling = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => fake()->words(2, true),
            'sort_order' => $i + 1,
        ]);
        $siblings[] = $sibling;
    }

    // Verify parent has all children
    $parent->refresh();
    expect($parent->children)->toHaveCount($siblingCount);
    
    foreach ($siblings as $sibling) {
        expect($parent->children->contains('id', $sibling->id))->toBeTrue();
    }

    // Verify each sibling has correct parent and depth
    foreach ($siblings as $sibling) {
        expect($sibling->parent_id)->toBe($parent->id);
        expect($sibling->getDepth())->toBe(1);
        expect($sibling->getRoot()->id)->toBe($parent->id);
        expect($sibling->ancestors()->first()->id)->toBe($parent->id);
    }

    // Verify siblings don't have ancestor/descendant relationships with each other
    for ($i = 0; $i < $siblingCount; $i++) {
        for ($j = 0; $j < $siblingCount; $j++) {
            if ($i !== $j) {
                expect($siblings[$i]->isAncestorOf($siblings[$j]))->toBeFalse();
                expect($siblings[$i]->isDescendantOf($siblings[$j]))->toBeFalse();
            }
        }
    }
})->repeat(100);

// Property: Category hierarchy maintains sort order consistency
test('property: category hierarchy maintains sort order within levels', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create parent category
    $parent = ProductCategory::create([
        'team_id' => $team->id,
        'name' => fake()->words(2, true),
        'sort_order' => 1,
    ]);

    // Create children with specific sort orders
    $childrenCount = fake()->numberBetween(3, 6);
    $sortOrders = range(1, $childrenCount);
    shuffle($sortOrders); // Randomize creation order
    
    $children = [];
    foreach ($sortOrders as $index => $sortOrder) {
        $child = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => "Child {$sortOrder}",
            'sort_order' => $sortOrder,
        ]);
        $children[] = $child;
    }

    // Verify children are returned in sort order
    $parent->refresh();
    $orderedChildren = $parent->children;
    
    expect($orderedChildren)->toHaveCount($childrenCount);
    
    // Check that children are ordered by sort_order
    $previousSortOrder = 0;
    foreach ($orderedChildren as $child) {
        expect($child->sort_order)->toBeGreaterThan($previousSortOrder);
        $previousSortOrder = $child->sort_order;
    }

    // Verify the first child has the lowest sort_order
    expect($orderedChildren->first()->sort_order)->toBe(1);
    
    // Verify the last child has the highest sort_order
    expect($orderedChildren->last()->sort_order)->toBe($childrenCount);
})->repeat(100);

// Property: Category hierarchy breadcrumb generation is consistent
test('property: category hierarchy generates correct breadcrumbs', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create a hierarchy with known names
    $depth = fake()->numberBetween(2, 4);
    $categoryNames = [];
    $categories = [];
    
    for ($i = 0; $i < $depth; $i++) {
        $name = "Level {$i} " . fake()->word();
        $categoryNames[] = $name;
        
        $category = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $i > 0 ? $categories[$i - 1]->id : null,
            'name' => $name,
            'sort_order' => $i + 1,
        ]);
        $categories[] = $category;
    }

    // Verify breadcrumb for each category
    for ($i = 0; $i < $depth; $i++) {
        $category = $categories[$i];
        $expectedBreadcrumb = implode(' / ', array_slice($categoryNames, 0, $i + 1));
        
        expect($category->breadcrumb)->toBe($expectedBreadcrumb);
    }

    // Verify root category breadcrumb is just its name
    expect($categories[0]->breadcrumb)->toBe($categoryNames[0]);
    
    // Verify leaf category breadcrumb contains all ancestors
    $leafCategory = end($categories);
    $fullBreadcrumb = implode(' / ', $categoryNames);
    expect($leafCategory->breadcrumb)->toBe($fullBreadcrumb);
})->repeat(100);

// Property: Category hierarchy deletion maintains referential integrity
test('property: category hierarchy handles deletion correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create a 3-level hierarchy
    $grandparent = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Grandparent',
        'sort_order' => 1,
    ]);

    $parent = ProductCategory::create([
        'team_id' => $team->id,
        'parent_id' => $grandparent->id,
        'name' => 'Parent',
        'sort_order' => 1,
    ]);

    $child = ProductCategory::create([
        'team_id' => $team->id,
        'parent_id' => $parent->id,
        'name' => 'Child',
        'sort_order' => 1,
    ]);

    // Verify initial hierarchy
    expect($grandparent->children)->toHaveCount(1);
    expect($parent->children)->toHaveCount(1);
    expect($child->parent_id)->toBe($parent->id);

    // Delete parent category (should set child's parent_id to null due to nullOnDelete)
    $parent->delete();

    // Refresh models
    $grandparent->refresh();
    $child->refresh();

    // Verify hierarchy is maintained correctly after deletion
    expect($grandparent->children)->toHaveCount(0);
    expect($child->parent_id)->toBeNull();
    expect($child->getDepth())->toBe(0);
    expect($child->ancestors())->toHaveCount(0);
})->repeat(100);