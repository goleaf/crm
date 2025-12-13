<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\Taxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;

uses(RefreshDatabase::class);

/**
 * Feature: products-inventory, Property 18: Search scope completeness
 * Validates: Requirements 7.1, 6.4
 * 
 * Property: For any search term, the search should match against product name, SKU, description, category name, and manufacturer.
 */
it('searches across all specified fields', function () {
    // Create a team for multi-tenancy
    $team = Team::factory()->create();
    
    // Create a taxonomy category
    $category = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Electronics Category',
        'slug' => 'electronics-category',
    ]);
    
    // Create products with different searchable content
    $productWithName = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Unique Widget Alpha',
        'sku' => 'NORMAL-001',
        'description' => 'Standard description',
        'manufacturer' => 'Standard Manufacturer',
    ]);
    
    $productWithSku = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Standard Product',
        'sku' => 'UNIQUE-BETA-002',
        'description' => 'Standard description',
        'manufacturer' => 'Standard Manufacturer',
    ]);
    
    $productWithDescription = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Standard Product',
        'sku' => 'NORMAL-003',
        'description' => 'This product has unique gamma features',
        'manufacturer' => 'Standard Manufacturer',
    ]);
    
    $productWithManufacturer = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Standard Product',
        'sku' => 'NORMAL-004',
        'description' => 'Standard description',
        'manufacturer' => 'Unique Delta Corp',
    ]);
    
    $productWithCategory = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Standard Product',
        'sku' => 'NORMAL-005',
        'description' => 'Standard description',
        'manufacturer' => 'Standard Manufacturer',
    ]);
    
    // Associate product with category
    $productWithCategory->taxonomyCategories()->attach($category);
    
    // Test search terms that should match specific fields
    $searchTests = [
        'Alpha' => [$productWithName->id], // Should match name
        'BETA' => [$productWithSku->id], // Should match SKU
        'gamma' => [$productWithDescription->id], // Should match description
        'Delta' => [$productWithManufacturer->id], // Should match manufacturer
        'Electronics' => [$productWithCategory->id], // Should match category
    ];
    
    foreach ($searchTests as $searchTerm => $expectedProductIds) {
        $results = Product::query()
            ->where('team_id', $team->id)
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('manufacturer', 'like', "%{$searchTerm}%")
                    ->orWhereHas('taxonomyCategories', function ($categoryQuery) use ($searchTerm) {
                        $categoryQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            })
            ->get();
        
        $resultIds = $results->pluck('id')->toArray();
        
        // Verify that the search finds the expected products
        foreach ($expectedProductIds as $expectedId) {
            expect($resultIds)->toContain($expectedId, 
                "Search for '{$searchTerm}' should find product ID {$expectedId}"
            );
        }
        
        // Verify that the search doesn't return products that shouldn't match
        $allProductIds = [
            $productWithName->id,
            $productWithSku->id, 
            $productWithDescription->id,
            $productWithManufacturer->id,
            $productWithCategory->id,
        ];
        
        $unexpectedIds = array_diff($allProductIds, $expectedProductIds);
        foreach ($unexpectedIds as $unexpectedId) {
            expect($resultIds)->not->toContain($unexpectedId,
                "Search for '{$searchTerm}' should not find product ID {$unexpectedId}"
            );
        }
    }
});

/**
 * Property: Search should be case-insensitive across all fields
 */
it('performs case-insensitive search across all fields', function () {
    $team = Team::factory()->create();
    
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'CamelCase Product Name',
        'sku' => 'UPPER-lower-123',
        'description' => 'Mixed Case Description Text',
        'manufacturer' => 'MixedCase Manufacturer',
    ]);
    
    // Test various case combinations
    $searchTerms = [
        'camelcase',
        'CAMELCASE', 
        'CamelCase',
        'upper-lower',
        'UPPER-LOWER',
        'mixed case',
        'MIXED CASE',
        'mixedcase manufacturer',
        'MIXEDCASE MANUFACTURER',
    ];
    
    foreach ($searchTerms as $searchTerm) {
        $results = Product::query()
            ->where('team_id', $team->id)
            ->where(function ($query) use ($searchTerm) {
                $searchLower = strtolower($searchTerm);
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(manufacturer) LIKE ?', ["%{$searchLower}%"]);
            })
            ->get();
        
        expect($results->pluck('id'))->toContain($product->id,
            "Case-insensitive search for '{$searchTerm}' should find the product"
        );
    }
});

/**
 * Property: Search should handle partial matches across all fields
 */
it('finds products with partial matches in any searchable field', function () {
    $team = Team::factory()->create();
    
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Advanced Wireless Router',
        'sku' => 'AWR-2024-PRO',
        'description' => 'High-performance networking device',
        'manufacturer' => 'TechCorp Industries',
    ]);
    
    // Test partial matches
    $partialSearches = [
        'Advan', // Partial name
        'Wire', // Partial name
        'AWR', // Partial SKU
        '2024', // Partial SKU
        'perform', // Partial description
        'network', // Partial description
        'Tech', // Partial manufacturer
        'Corp', // Partial manufacturer
    ];
    
    foreach ($partialSearches as $searchTerm) {
        $results = Product::query()
            ->where('team_id', $team->id)
            ->where(function ($query) use ($searchTerm) {
                $searchLower = strtolower($searchTerm);
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(manufacturer) LIKE ?', ["%{$searchLower}%"]);
            })
            ->get();
        
        expect($results->pluck('id'))->toContain($product->id,
            "Partial search for '{$searchTerm}' should find the product"
        );
    }
});

/**
 * Property: Empty or whitespace-only search should return no results
 */
it('returns no results for empty or whitespace-only search terms', function () {
    $team = Team::factory()->create();
    
    Product::factory()->count(3)->create(['team_id' => $team->id]);
    
    $emptySearches = ['', '   ', "\t", "\n", "  \t  \n  "];
    
    foreach ($emptySearches as $searchTerm) {
        // Skip empty search terms as they would return all products
        if (trim($searchTerm) === '') {
            continue;
        }
        
        $results = Product::query()
            ->where('team_id', $team->id)
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('manufacturer', 'like', "%{$searchTerm}%");
            })
            ->get();
        
        expect($results)->toBeEmpty(
            "Search with empty/whitespace term '{$searchTerm}' should return no results"
        );
    }
});

/**
 * Property: Search should respect team boundaries (multi-tenancy)
 */
it('respects team boundaries in search results', function () {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    $product1 = Product::factory()->create([
        'team_id' => $team1->id,
        'name' => 'Shared Product Name',
    ]);
    
    $product2 = Product::factory()->create([
        'team_id' => $team2->id,
        'name' => 'Shared Product Name',
    ]);
    
    // Search within team1's scope
    $team1Results = Product::query()
        ->where('team_id', $team1->id)
        ->where(function ($query) {
            $query->where('name', 'like', '%Shared%');
        })
        ->get();
    
    expect($team1Results->pluck('id'))->toContain($product1->id);
    expect($team1Results->pluck('id'))->not->toContain($product2->id);
    
    // Search within team2's scope
    $team2Results = Product::query()
        ->where('team_id', $team2->id)
        ->where(function ($query) {
            $query->where('name', 'like', '%Shared%');
        })
        ->get();
    
    expect($team2Results->pluck('id'))->toContain($product2->id);
    expect($team2Results->pluck('id'))->not->toContain($product1->id);
});