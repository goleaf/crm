<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Feature: products-inventory, Property 7: Category filtering includes subcategories**
 *
 * **Validates: Requirements 2.4**
 *
 * Property: For any category filter, the results should include all products in that category
 * and all its descendant subcategories.
 */

// Property: Category filtering includes products from subcategories
test('property: category filtering includes products from all subcategories', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create a random hierarchy depth (2-4 levels)
    $depth = fake()->numberBetween(2, 4);
    $categories = [];
    
    // Create root category
    $rootCategory = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Root Category',
        'sort_order' => 1,
    ]);
    $categories[] = $rootCategory;

    // Create nested categories
    $parent = $rootCategory;
    for ($i = 1; $i < $depth; $i++) {
        $child = ProductCategory::create([
            'team_id' => $team->id,
            'parent_id' => $parent->id,
            'name' => "Level {$i} Category",
            'sort_order' => 1,
        ]);
        $categories[] = $child;
        $parent = $child;
    }

    // Create products in each category
    $productsPerCategory = fake()->numberBetween(1, 3);
    $allProducts = collect();
    
    foreach ($categories as $index => $category) {
        for ($j = 0; $j < $productsPerCategory; $j++) {
            $product = Product::factory()->create([
                'team_id' => $team->id,
                'name' => "Product L{$index}-{$j}",
            ]);
            
            // Associate product with category
            $product->categories()->attach($category->id);
            $allProducts->push($product);
        }
    }

    // Create some products not in any of these categories
    $unrelatedProducts = collect();
    for ($i = 0; $i < fake()->numberBetween(1, 2); $i++) {
        $unrelatedCategory = ProductCategory::create([
            'team_id' => $team->id,
            'name' => "Unrelated Category {$i}",
            'sort_order' => 1,
        ]);
        
        $unrelatedProduct = Product::factory()->create([
            'team_id' => $team->id,
            'name' => "Unrelated Product {$i}",
        ]);
        $unrelatedProduct->categories()->attach($unrelatedCategory->id);
        $unrelatedProducts->push($unrelatedProduct);
    }

    // Test filtering by root category includes all products in hierarchy
    $rootCategoryProducts = $rootCategory->allProducts();
    
    // Verify all products in the hierarchy are included
    expect($rootCategoryProducts->count())->toBe($allProducts->count());
    
    foreach ($allProducts as $product) {
        expect($rootCategoryProducts->contains('id', $product->id))->toBeTrue();
    }
    
    // Verify unrelated products are not included
    foreach ($unrelatedProducts as $unrelatedProduct) {
        expect($rootCategoryProducts->contains('id', $unrelatedProduct->id))->toBeFalse();
    }

    // Test filtering by intermediate categories
    for ($i = 1; $i < $depth; $i++) {
        $category = $categories[$i];
        $categoryProducts = $category->allProducts();
        
        // Should include products from this category and all its descendants
        $expectedCount = ($depth - $i) * $productsPerCategory;
        expect($categoryProducts->count())->toBe($expectedCount);
        
        // Verify it includes products from descendants but not ancestors
        for ($j = $i; $j < $depth; $j++) {
            $descendantCategory = $categories[$j];
            $descendantProducts = Product::whereHas('categories', function ($query) use ($descendantCategory) {
                $query->where('product_categories.id', $descendantCategory->id);
            })->get();
            
            foreach ($descendantProducts as $product) {
                expect($categoryProducts->contains('id', $product->id))->toBeTrue();
            }
        }
        
        // Verify it doesn't include products from ancestors
        for ($j = 0; $j < $i; $j++) {
            $ancestorCategory = $categories[$j];
            $ancestorOnlyProducts = Product::whereHas('categories', function ($query) use ($ancestorCategory, $categories, $i) {
                $query->where('product_categories.id', $ancestorCategory->id)
                      ->whereNotIn('product_categories.id', collect($categories)->slice($i)->pluck('id'));
            })->get();
            
            foreach ($ancestorOnlyProducts as $product) {
                expect($categoryProducts->contains('id', $product->id))->toBeFalse();
            }
        }
    }
})->repeat(100);

// Property: Category filtering with multiple category assignments works correctly
test('property: category filtering handles products with multiple category assignments', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create two separate category hierarchies
    $hierarchy1Root = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Hierarchy 1 Root',
        'sort_order' => 1,
    ]);
    
    $hierarchy1Child = ProductCategory::create([
        'team_id' => $team->id,
        'parent_id' => $hierarchy1Root->id,
        'name' => 'Hierarchy 1 Child',
        'sort_order' => 1,
    ]);

    $hierarchy2Root = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Hierarchy 2 Root',
        'sort_order' => 1,
    ]);
    
    $hierarchy2Child = ProductCategory::create([
        'team_id' => $team->id,
        'parent_id' => $hierarchy2Root->id,
        'name' => 'Hierarchy 2 Child',
        'sort_order' => 1,
    ]);

    // Create products with different category assignments
    $productInH1Root = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product in H1 Root only',
    ]);
    $productInH1Root->categories()->attach($hierarchy1Root->id);

    $productInH1Child = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product in H1 Child only',
    ]);
    $productInH1Child->categories()->attach($hierarchy1Child->id);

    $productInBothRoots = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product in both roots',
    ]);
    $productInBothRoots->categories()->attach([$hierarchy1Root->id, $hierarchy2Root->id]);

    $productInH1ChildAndH2Root = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product in H1 Child and H2 Root',
    ]);
    $productInH1ChildAndH2Root->categories()->attach([$hierarchy1Child->id, $hierarchy2Root->id]);

    // Test filtering by hierarchy 1 root
    $h1RootProducts = $hierarchy1Root->allProducts();
    
    // Should include products directly in H1 root, in H1 child, and cross-assigned products
    expect($h1RootProducts->contains('id', $productInH1Root->id))->toBeTrue();
    expect($h1RootProducts->contains('id', $productInH1Child->id))->toBeTrue();
    expect($h1RootProducts->contains('id', $productInBothRoots->id))->toBeTrue();
    expect($h1RootProducts->contains('id', $productInH1ChildAndH2Root->id))->toBeTrue();
    
    // Test filtering by hierarchy 1 child
    $h1ChildProducts = $hierarchy1Child->allProducts();
    
    // Should include products in H1 child and cross-assigned products
    expect($h1ChildProducts->contains('id', $productInH1Child->id))->toBeTrue();
    expect($h1ChildProducts->contains('id', $productInH1ChildAndH2Root->id))->toBeTrue();
    
    // Should not include products only in H1 root or only in H2
    expect($h1ChildProducts->contains('id', $productInH1Root->id))->toBeFalse();
    expect($h1ChildProducts->contains('id', $productInBothRoots->id))->toBeFalse();

    // Test filtering by hierarchy 2 root
    $h2RootProducts = $hierarchy2Root->allProducts();
    
    // Should include products in H2 hierarchy
    expect($h2RootProducts->contains('id', $productInBothRoots->id))->toBeTrue();
    expect($h2RootProducts->contains('id', $productInH1ChildAndH2Root->id))->toBeTrue();
    
    // Should not include products only in H1
    expect($h2RootProducts->contains('id', $productInH1Root->id))->toBeFalse();
    expect($h2RootProducts->contains('id', $productInH1Child->id))->toBeFalse();
})->repeat(100);

// Property: Category filtering respects team boundaries
test('property: category filtering respects team boundaries', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach([$team1->id, $team2->id]);
    $user->switchTeam($team1);

    $this->actingAs($user);

    // Create categories in both teams with same names
    $team1Category = ProductCategory::create([
        'team_id' => $team1->id,
        'name' => 'Shared Category Name',
        'sort_order' => 1,
    ]);
    
    $team1SubCategory = ProductCategory::create([
        'team_id' => $team1->id,
        'parent_id' => $team1Category->id,
        'name' => 'Shared Sub Category Name',
        'sort_order' => 1,
    ]);

    $team2Category = ProductCategory::create([
        'team_id' => $team2->id,
        'name' => 'Shared Category Name',
        'sort_order' => 1,
    ]);
    
    $team2SubCategory = ProductCategory::create([
        'team_id' => $team2->id,
        'parent_id' => $team2Category->id,
        'name' => 'Shared Sub Category Name',
        'sort_order' => 1,
    ]);

    // Create products in each team's categories
    $team1Product = Product::factory()->create([
        'team_id' => $team1->id,
        'name' => 'Team 1 Product',
    ]);
    $team1Product->categories()->attach($team1SubCategory->id);

    $team2Product = Product::factory()->create([
        'team_id' => $team2->id,
        'name' => 'Team 2 Product',
    ]);
    $team2Product->categories()->attach($team2SubCategory->id);

    // Test filtering by team 1 category
    $team1CategoryProducts = $team1Category->allProducts();
    
    // Should only include products from team 1
    expect($team1CategoryProducts->contains('id', $team1Product->id))->toBeTrue();
    expect($team1CategoryProducts->contains('id', $team2Product->id))->toBeFalse();
    
    // Verify all returned products belong to team 1
    foreach ($team1CategoryProducts as $product) {
        expect($product->team_id)->toBe($team1->id);
    }

    // Test filtering by team 2 category
    $team2CategoryProducts = $team2Category->allProducts();
    
    // Should only include products from team 2
    expect($team2CategoryProducts->contains('id', $team2Product->id))->toBeTrue();
    expect($team2CategoryProducts->contains('id', $team1Product->id))->toBeFalse();
    
    // Verify all returned products belong to team 2
    foreach ($team2CategoryProducts as $product) {
        expect($product->team_id)->toBe($team2->id);
    }
})->repeat(100);

// Property: Category filtering with empty categories returns empty results
test('property: category filtering with empty categories returns empty results', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create a category hierarchy with no products
    $emptyRoot = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Empty Root Category',
        'sort_order' => 1,
    ]);
    
    $emptyChild = ProductCategory::create([
        'team_id' => $team->id,
        'parent_id' => $emptyRoot->id,
        'name' => 'Empty Child Category',
        'sort_order' => 1,
    ]);
    
    $emptyGrandchild = ProductCategory::create([
        'team_id' => $team->id,
        'parent_id' => $emptyChild->id,
        'name' => 'Empty Grandchild Category',
        'sort_order' => 1,
    ]);

    // Create some products in other categories to ensure they don't leak
    $otherCategory = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Other Category',
        'sort_order' => 1,
    ]);
    
    $otherProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Other Product',
    ]);
    $otherProduct->categories()->attach($otherCategory->id);

    // Test filtering by empty categories
    expect($emptyRoot->allProducts())->toHaveCount(0);
    expect($emptyChild->allProducts())->toHaveCount(0);
    expect($emptyGrandchild->allProducts())->toHaveCount(0);
    
    // Verify the other category still has its product
    expect($otherCategory->allProducts())->toHaveCount(1);
    expect($otherCategory->allProducts()->first()->id)->toBe($otherProduct->id);
})->repeat(100);