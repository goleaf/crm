<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Team;
use App\Models\User;
use App\Services\Products\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: products-inventory, Property 15: Available inventory calculation
// For any product or variation, the available inventory should equal current quantity minus reserved quantity.
// Validates: Requirements 5.4

it('calculates available inventory correctly for products', function (): void {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $inventoryService = resolve(InventoryService::class);

    // Property: Available inventory = current quantity - reserved quantity
    for ($i = 0; $i < 100; $i++) {
        // Generate random inventory quantities
        $currentQuantity = fake()->numberBetween(0, 1000);
        $reservedQuantity = fake()->numberBetween(0, $currentQuantity);

        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $currentQuantity,
            'reserved_quantity' => $reservedQuantity,
        ]);

        $expectedAvailable = $currentQuantity - $reservedQuantity;

        // Test service method
        $serviceAvailable = $inventoryService->getAvailableQuantity($product);
        expect($serviceAvailable)->toBe($expectedAvailable);

        // Test model method
        $modelAvailable = $product->availableInventory();
        expect($modelAvailable)->toBe($expectedAvailable);

        // Verify consistency between service and model
        expect($serviceAvailable)->toBe($modelAvailable);

        // Verify non-negative result
        expect($serviceAvailable)->toBeGreaterThanOrEqual(0);
    }
});

it('calculates available inventory correctly for variations', function (): void {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $inventoryService = resolve(InventoryService::class);

    // Property: Available inventory = current quantity - reserved quantity for variations
    for ($i = 0; $i < 100; $i++) {
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
        ]);

        // Generate random inventory quantities
        $currentQuantity = fake()->numberBetween(0, 1000);
        $reservedQuantity = fake()->numberBetween(0, $currentQuantity);

        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'track_inventory' => true,
            'inventory_quantity' => $currentQuantity,
            'reserved_quantity' => $reservedQuantity,
        ]);

        $expectedAvailable = $currentQuantity - $reservedQuantity;

        // Test service method
        $serviceAvailable = $inventoryService->getAvailableQuantity($variation);
        expect($serviceAvailable)->toBe($expectedAvailable);

        // Test model method
        $modelAvailable = $variation->availableInventory();
        expect($modelAvailable)->toBe($expectedAvailable);

        // Verify consistency between service and model
        expect($serviceAvailable)->toBe($modelAvailable);

        // Verify non-negative result
        expect($serviceAvailable)->toBeGreaterThanOrEqual(0);
    }
});

it('handles inventory reservation correctly', function (): void {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $inventoryService = resolve(InventoryService::class);

    // Property: Reserving inventory should reduce available quantity
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(50, 1000);

        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);

        $reservationQuantity = fake()->numberBetween(1, $initialQuantity);

        // Check initial available quantity
        $initialAvailable = $inventoryService->getAvailableQuantity($product);
        expect($initialAvailable)->toBe($initialQuantity);

        // Reserve inventory
        $reservationSuccess = $inventoryService->reserveInventory($product, $reservationQuantity);
        expect($reservationSuccess)->toBeTrue();

        // Refresh product from database
        $product->refresh();

        // Check available quantity after reservation
        $availableAfterReservation = $inventoryService->getAvailableQuantity($product);
        $expectedAvailable = $initialQuantity - $reservationQuantity;

        expect($availableAfterReservation)->toBe($expectedAvailable);
        expect($product->reserved_quantity)->toBe($reservationQuantity);
        expect($product->inventory_quantity)->toBe($initialQuantity); // Should not change
    }
});

it('handles inventory release correctly', function (): void {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $inventoryService = resolve(InventoryService::class);

    // Property: Releasing inventory should increase available quantity
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(50, 1000);
        $initialReserved = fake()->numberBetween(10, $initialQuantity);

        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => $initialReserved,
        ]);

        $releaseQuantity = fake()->numberBetween(1, $initialReserved);

        // Check initial available quantity
        $initialAvailable = $inventoryService->getAvailableQuantity($product);
        expect($initialAvailable)->toBe($initialQuantity - $initialReserved);

        // Release inventory
        $inventoryService->releaseInventory($product, $releaseQuantity);

        // Refresh product from database
        $product->refresh();

        // Check available quantity after release
        $availableAfterRelease = $inventoryService->getAvailableQuantity($product);
        $expectedAvailable = $initialQuantity - ($initialReserved - $releaseQuantity);

        expect($availableAfterRelease)->toBe($expectedAvailable);
        expect($product->reserved_quantity)->toBe($initialReserved - $releaseQuantity);
        expect($product->inventory_quantity)->toBe($initialQuantity); // Should not change
    }
});

it('handles products with variations correctly', function (): void {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $inventoryService = resolve(InventoryService::class);

    // Property: Product with variations should aggregate available inventory
    for ($i = 0; $i < 50; $i++) {
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => 0, // Parent product has no direct inventory
            'reserved_quantity' => 0,
        ]);

        $variationCount = fake()->numberBetween(2, 5);
        $totalExpectedAvailable = 0;

        // Create variations with random inventory
        for ($j = 0; $j < $variationCount; $j++) {
            $variationQuantity = fake()->numberBetween(0, 100);
            $variationReserved = fake()->numberBetween(0, $variationQuantity);

            ProductVariation::factory()->create([
                'product_id' => $product->id,
                'track_inventory' => true,
                'inventory_quantity' => $variationQuantity,
                'reserved_quantity' => $variationReserved,
            ]);

            $totalExpectedAvailable += ($variationQuantity - $variationReserved);
        }

        // Refresh product to load variations
        $product->refresh();

        // Check product's available inventory (should aggregate variations)
        $productAvailable = $inventoryService->getAvailableQuantity($product);
        expect($productAvailable)->toBe($totalExpectedAvailable);

        // Verify model method consistency
        $modelAvailable = $product->availableInventory();
        expect($modelAvailable)->toBe($totalExpectedAvailable);
    }
});

it('returns unlimited inventory for non-tracked items', function (): void {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $inventoryService = resolve(InventoryService::class);

    // Property: Non-tracked items should have unlimited availability
    for ($i = 0; $i < 100; $i++) {
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => false, // Not tracking inventory
            'inventory_quantity' => fake()->numberBetween(0, 1000),
            'reserved_quantity' => fake()->numberBetween(0, 100),
        ]);

        // Service should return unlimited (PHP_INT_MAX)
        $serviceAvailable = $inventoryService->getAvailableQuantity($product);
        expect($serviceAvailable)->toBe(PHP_INT_MAX);

        // Model method should return true for isInStock
        expect($product->isInStock())->toBeTrue();
    }
});

it('maintains calculation consistency after adjustments', function (): void {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $inventoryService = resolve(InventoryService::class);

    // Property: Available inventory calculation should remain consistent after adjustments
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(50, 500);

        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);

        // Make random adjustments and reservations
        $adjustmentQuantity = fake()->numberBetween(-20, 50);
        $reservationQuantity = fake()->numberBetween(0, 30);

        // Adjust inventory
        $inventoryService->adjustInventory($product, $adjustmentQuantity, 'Test adjustment');

        // Reserve some inventory if there's enough
        $product->refresh();
        if ($product->inventory_quantity >= $reservationQuantity) {
            $inventoryService->reserveInventory($product, $reservationQuantity);
        } else {
            $reservationQuantity = 0; // No reservation made
        }

        // Refresh and calculate expected available
        $product->refresh();
        $expectedAvailable = max(0, $product->inventory_quantity - $product->reserved_quantity);

        // Verify calculation consistency
        $serviceAvailable = $inventoryService->getAvailableQuantity($product);
        $modelAvailable = $product->availableInventory();

        expect($serviceAvailable)->toBe($expectedAvailable);
        expect($modelAvailable)->toBe($expectedAvailable);
        expect($serviceAvailable)->toBe($modelAvailable);
    }
});
