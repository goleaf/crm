<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Taxonomy;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: products-inventory, Property 19: Category filter accuracy
 * Validates: Requirements 7.2
 *
 * Property: For any category filter selection, the results should include only products associated with the selected categories.
 */
it('filters products by selected categories accurately', function (): void {
    $team = Team::factory()->create();

    // Create categories
    $electronicsCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Electronics',
        'slug' => 'electronics',
    ]);

    $clothingCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Clothing',
        'slug' => 'clothing',
    ]);

    $booksCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Books',
        'slug' => 'books',
    ]);

    // Create products
    $electronicsProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Laptop Computer',
    ]);

    $clothingProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Cotton T-Shirt',
    ]);

    $bookProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Programming Guide',
    ]);

    $uncategorizedProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Uncategorized Item',
    ]);

    // Associate products with categories
    $electronicsProduct->taxonomyCategories()->attach($electronicsCategory);
    $clothingProduct->taxonomyCategories()->attach($clothingCategory);
    $bookProduct->taxonomyCategories()->attach($booksCategory);
    // uncategorizedProduct has no category associations

    // Test filtering by single category
    $electronicsResults = Product::query()
        ->where('team_id', $team->id)
        ->whereHas('taxonomyCategories', function ($query) use ($electronicsCategory): void {
            $query->where('taxonomies.id', $electronicsCategory->id);
        })
        ->get();

    expect($electronicsResults->pluck('id'))->toContain($electronicsProduct->id);
    expect($electronicsResults->pluck('id'))->not->toContain($clothingProduct->id);
    expect($electronicsResults->pluck('id'))->not->toContain($bookProduct->id);
    expect($electronicsResults->pluck('id'))->not->toContain($uncategorizedProduct->id);

    // Test filtering by multiple categories
    $multiCategoryResults = Product::query()
        ->where('team_id', $team->id)
        ->whereHas('taxonomyCategories', function ($query) use ($electronicsCategory, $clothingCategory): void {
            $query->whereIn('taxonomies.id', [$electronicsCategory->id, $clothingCategory->id]);
        })
        ->get();

    expect($multiCategoryResults->pluck('id'))->toContain($electronicsProduct->id);
    expect($multiCategoryResults->pluck('id'))->toContain($clothingProduct->id);
    expect($multiCategoryResults->pluck('id'))->not->toContain($bookProduct->id);
    expect($multiCategoryResults->pluck('id'))->not->toContain($uncategorizedProduct->id);
});

/**
 * Property: Category filter should include products from subcategories
 */
it('includes products from subcategories when filtering by parent category', function (): void {
    $team = Team::factory()->create();

    // Create parent category
    $electronicsCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Electronics',
        'slug' => 'electronics',
        'parent_id' => null,
    ]);

    // Create subcategories
    $computersCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Computers',
        'slug' => 'computers',
        'parent_id' => $electronicsCategory->id,
    ]);

    $phonesCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Phones',
        'slug' => 'phones',
        'parent_id' => $electronicsCategory->id,
    ]);

    // Create products
    $parentCategoryProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Generic Electronics Item',
    ]);

    $computerProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Laptop Computer',
    ]);

    $phoneProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Smartphone',
    ]);

    $unrelatedProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Book',
    ]);

    // Associate products with categories
    $parentCategoryProduct->taxonomyCategories()->attach($electronicsCategory);
    $computerProduct->taxonomyCategories()->attach($computersCategory);
    $phoneProduct->taxonomyCategories()->attach($phonesCategory);
    // unrelatedProduct has no category associations

    // Test filtering by parent category should include subcategory products
    $parentCategoryResults = Product::query()
        ->where('team_id', $team->id)
        ->whereHas('taxonomyCategories', function ($query) use ($electronicsCategory): void {
            $query->where('taxonomies.id', $electronicsCategory->id)
                ->orWhere('taxonomies.parent_id', $electronicsCategory->id);
        })
        ->get();

    expect($parentCategoryResults->pluck('id'))->toContain($parentCategoryProduct->id);
    expect($parentCategoryResults->pluck('id'))->toContain($computerProduct->id);
    expect($parentCategoryResults->pluck('id'))->toContain($phoneProduct->id);
    expect($parentCategoryResults->pluck('id'))->not->toContain($unrelatedProduct->id);
});

/**
 * Property: Products can belong to multiple categories and should appear in all relevant filters
 */
it('handles products with multiple categories correctly', function (): void {
    $team = Team::factory()->create();

    // Create categories
    $electronicsCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Electronics',
        'slug' => 'electronics',
    ]);

    $accessoriesCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Accessories',
        'slug' => 'accessories',
    ]);

    $giftCategory = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Gifts',
        'slug' => 'gifts',
    ]);

    // Create products
    $multiCategoryProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Wireless Headphones',
    ]);

    $singleCategoryProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Gift Card',
    ]);

    // Associate multi-category product with multiple categories
    $multiCategoryProduct->taxonomyCategories()->attach([
        $electronicsCategory->id,
        $accessoriesCategory->id,
        $giftCategory->id,
    ]);

    // Associate single-category product with one category
    $singleCategoryProduct->taxonomyCategories()->attach($giftCategory);

    // Test that multi-category product appears in all relevant filters
    $electronicsResults = Product::query()
        ->where('team_id', $team->id)
        ->whereHas('taxonomyCategories', function ($query) use ($electronicsCategory): void {
            $query->where('taxonomies.id', $electronicsCategory->id);
        })
        ->get();

    expect($electronicsResults->pluck('id'))->toContain($multiCategoryProduct->id);
    expect($electronicsResults->pluck('id'))->not->toContain($singleCategoryProduct->id);

    $accessoriesResults = Product::query()
        ->where('team_id', $team->id)
        ->whereHas('taxonomyCategories', function ($query) use ($accessoriesCategory): void {
            $query->where('taxonomies.id', $accessoriesCategory->id);
        })
        ->get();

    expect($accessoriesResults->pluck('id'))->toContain($multiCategoryProduct->id);
    expect($accessoriesResults->pluck('id'))->not->toContain($singleCategoryProduct->id);

    $giftResults = Product::query()
        ->where('team_id', $team->id)
        ->whereHas('taxonomyCategories', function ($query) use ($giftCategory): void {
            $query->where('taxonomies.id', $giftCategory->id);
        })
        ->get();

    expect($giftResults->pluck('id'))->toContain($multiCategoryProduct->id);
    expect($giftResults->pluck('id'))->toContain($singleCategoryProduct->id);
});

/**
 * Property: Category filter should respect team boundaries
 */
it('respects team boundaries when filtering by categories', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    // Create categories for each team
    $team1Category = Taxonomy::create([
        'team_id' => $team1->id,
        'type' => 'product_category',
        'name' => 'Team 1 Category',
        'slug' => 'team-1-category',
    ]);

    $team2Category = Taxonomy::create([
        'team_id' => $team2->id,
        'type' => 'product_category',
        'name' => 'Team 2 Category',
        'slug' => 'team-2-category',
    ]);

    // Create products for each team
    $team1Product = Product::factory()->create([
        'team_id' => $team1->id,
        'name' => 'Team 1 Product',
    ]);

    $team2Product = Product::factory()->create([
        'team_id' => $team2->id,
        'name' => 'Team 2 Product',
    ]);

    // Associate products with their team's categories
    $team1Product->taxonomyCategories()->attach($team1Category);
    $team2Product->taxonomyCategories()->attach($team2Category);

    // Test that team 1 filter only returns team 1 products
    $team1Results = Product::query()
        ->where('team_id', $team1->id)
        ->whereHas('taxonomyCategories', function ($query) use ($team1Category): void {
            $query->where('taxonomies.id', $team1Category->id);
        })
        ->get();

    expect($team1Results->pluck('id'))->toContain($team1Product->id);
    expect($team1Results->pluck('id'))->not->toContain($team2Product->id);

    // Test that team 2 filter only returns team 2 products
    $team2Results = Product::query()
        ->where('team_id', $team2->id)
        ->whereHas('taxonomyCategories', function ($query) use ($team2Category): void {
            $query->where('taxonomies.id', $team2Category->id);
        })
        ->get();

    expect($team2Results->pluck('id'))->toContain($team2Product->id);
    expect($team2Results->pluck('id'))->not->toContain($team1Product->id);
});

/**
 * Property: Empty category filter should return all products
 */
it('returns all products when no category filter is applied', function (): void {
    $team = Team::factory()->create();

    // Create categories
    $category1 = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Category 1',
        'slug' => 'category-1',
    ]);

    $category2 = Taxonomy::create([
        'team_id' => $team->id,
        'type' => 'product_category',
        'name' => 'Category 2',
        'slug' => 'category-2',
    ]);

    // Create products
    $categorizedProduct1 = Product::factory()->create(['team_id' => $team->id]);
    $categorizedProduct2 = Product::factory()->create(['team_id' => $team->id]);
    $uncategorizedProduct = Product::factory()->create(['team_id' => $team->id]);

    // Associate some products with categories
    $categorizedProduct1->taxonomyCategories()->attach($category1);
    $categorizedProduct2->taxonomyCategories()->attach($category2);
    // uncategorizedProduct has no category associations

    // Test that no filter returns all products
    $allResults = Product::query()
        ->where('team_id', $team->id)
        ->get();

    expect($allResults->pluck('id'))->toContain($categorizedProduct1->id);
    expect($allResults->pluck('id'))->toContain($categorizedProduct2->id);
    expect($allResults->pluck('id'))->toContain($uncategorizedProduct->id);
    expect($allResults)->toHaveCount(3);
});
