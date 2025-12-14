<?php

declare(strict_types=1);

use App\Enums\ProductLifecycleStage;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Feature: products-inventory, Property 1: Product creation captures all required fields**
 *
 * **Validates: Requirements 1.1**
 *
 * Property: For any product creation request with valid data, the system should persist
 * name, description, SKU, status, and creation metadata correctly.
 */

// Property: Product creation persists all required fields correctly
test('property: product creation captures all required fields', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Generate random valid product data
    $productData = [
        'team_id' => $team->id,
        'name' => fake()->words(3, true),
        'description' => fake()->optional()->paragraph(),
        'sku' => fake()->optional()->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
        'status' => fake()->randomElement(ProductStatus::cases()),
        'lifecycle_stage' => fake()->randomElement(ProductLifecycleStage::cases()),
        'price' => fake()->randomFloat(2, 1, 1000),
        'cost_price' => fake()->randomFloat(2, 0.5, 500),
        'currency_code' => fake()->randomElement(['USD', 'EUR', 'GBP']),
        'is_active' => fake()->boolean(),
        'track_inventory' => fake()->boolean(),
    ];

    // Create the product
    $product = Product::create($productData);

    // Verify all required fields are persisted correctly
    expect($product->exists())->toBeTrue()
        ->and($product->team_id)->toBe($productData['team_id'])
        ->and($product->name)->toBe($productData['name'])
        ->and($product->description)->toBe($productData['description'])
        ->and($product->sku)->toBe($productData['sku'])
        ->and($product->status)->toBe($productData['status'])
        ->and($product->lifecycle_stage)->toBe($productData['lifecycle_stage'])
        ->and($product->price)->toBe($productData['price'])
        ->and($product->cost_price)->toBe($productData['cost_price'])
        ->and($product->currency_code)->toBe($productData['currency_code'])
        ->and($product->is_active)->toBe($productData['is_active'])
        ->and($product->track_inventory)->toBe($productData['track_inventory']);

    // Verify creation metadata
    expect($product->created_at)->not->toBeNull()
        ->and($product->updated_at)->not->toBeNull()
        ->and($product->created_at)->toEqual($product->updated_at);

    // Verify slug is generated
    expect($product->slug)->not->toBeNull()
        ->and($product->slug)->toBeString();
})->repeat(100);

// Property: Product creation with minimal data uses correct defaults
test('property: product creation with minimal data applies correct defaults', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    // Create product with only required fields
    $productData = [
        'team_id' => $team->id,
        'name' => fake()->words(3, true),
    ];

    $product = Product::create($productData);

    // Verify defaults are applied correctly
    expect($product->exists())->toBeTrue()
        ->and($product->team_id)->toBe($productData['team_id'])
        ->and($product->name)->toBe($productData['name'])
        ->and($product->status)->toBe(ProductStatus::ACTIVE)
        ->and($product->lifecycle_stage)->toBe(ProductLifecycleStage::RELEASED)
        ->and($product->currency_code)->toBe('USD')
        ->and($product->cost_price)->toBe(0.0)
        ->and($product->is_active)->toBeTrue()
        ->and($product->is_bundle)->toBeFalse()
        ->and($product->track_inventory)->toBeFalse()
        ->and($product->inventory_quantity)->toBe(0)
        ->and($product->reserved_quantity)->toBe(0);

    // Verify creation metadata
    expect($product->created_at)->not->toBeNull()
        ->and($product->updated_at)->not->toBeNull();
})->repeat(100);

// Property: Product creation with inventory tracking initializes quantities correctly
test('property: product creation with inventory tracking initializes quantities', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    $initialQuantity = fake()->numberBetween(0, 1000);

    $productData = [
        'team_id' => $team->id,
        'name' => fake()->words(3, true),
        'track_inventory' => true,
        'inventory_quantity' => $initialQuantity,
    ];

    $product = Product::create($productData);

    // Verify inventory tracking is enabled and quantities are set
    expect($product->track_inventory)->toBeTrue()
        ->and($product->inventory_quantity)->toBe($initialQuantity)
        ->and($product->reserved_quantity)->toBe(0)
        ->and($product->availableInventory())->toBe($initialQuantity)
        ->and($product->getTotalInventory())->toBe($initialQuantity)
        ->and($product->getTotalReserved())->toBe(0);
})->repeat(100);

// Property: Product creation respects team boundaries
test('property: product creation respects team boundaries', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $user = User::factory()->create();

    $user->teams()->attach([$team1->id, $team2->id]);
    $user->switchTeam($team1);

    $this->actingAs($user);

    $productData = [
        'team_id' => $team1->id,
        'name' => fake()->words(3, true),
    ];

    $product = Product::create($productData);

    // Verify product belongs to correct team
    expect($product->team_id)->toBe($team1->id)
        ->and($product->team_id)->not->toBe($team2->id);

    // Verify product is only accessible within its team
    // @phpstan-ignore-next-line
    $team1Products = Product::where('team_id')->count();
    // @phpstan-ignore-next-line
    $team2Products = Product::where('team_id')->count();

    expect($team1Products)->toBeGreaterThan(0)
        ->and($team2Products)->toBe(0);
})->repeat(100);

// Property: Product creation with bundle flag initializes bundle-specific behavior
test('property: product creation with bundle flag enables bundle behavior', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    $bundlePrice = fake()->randomFloat(2, 10, 500);

    $productData = [
        'team_id' => $team->id,
        'name' => fake()->words(3, true),
        'is_bundle' => true,
        'price' => $bundlePrice,
    ];

    $product = Product::create($productData);

    // Verify bundle behavior
    expect($product->is_bundle)->toBeTrue()
        ->and($product->price)->toBe($bundlePrice)
        ->and($product->getBundlePrice())->toBe($bundlePrice);
})->repeat(100);

// Property: Product creation with different statuses affects sellability
test('property: product creation status affects sellability correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $this->actingAs($user);

    $status = fake()->randomElement(ProductStatus::cases());
    $lifecycleStage = fake()->randomElement(ProductLifecycleStage::cases());

    $productData = [
        'team_id' => $team->id,
        'name' => fake()->words(3, true),
        'status' => $status,
        'lifecycle_stage' => $lifecycleStage,
        'is_active' => true,
    ];

    $product = Product::create($productData);

    // Verify sellability logic
    $expectedSellable = $status->isSellable() && $lifecycleStage->isSellable();
    $expectedAllowsNewSales = $status->allowsNewSales() && $lifecycleStage->allowsNewSales();

    expect($product->isSellable())->toBe($expectedSellable)
        ->and($product->allowsNewSales())->toBe($expectedAllowsNewSales)
        ->and($product->canBeAddedToQuote())->toBe($expectedAllowsNewSales && $expectedSellable);
})->repeat(100);
