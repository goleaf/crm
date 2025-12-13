<?php

declare(strict_types=1);

use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Team;
use App\Models\User;
use App\Services\Products\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: products-inventory, Property 16: Automatic inventory decrement
// For any product sale, the system should automatically decrement the available inventory by the sold quantity.
// Validates: Requirements 5.5

it('automatically decrements inventory for product sales', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Sales should automatically decrement inventory
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(50, 1000);
        $saleQuantity = fake()->numberBetween(1, min(50, $initialQuantity));
        $saleId = fake()->uuid();
        
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);
        
        // Record initial available inventory
        $initialAvailable = $inventoryService->getAvailableQuantity($product);
        expect($initialAvailable)->toBe($initialQuantity);
        
        // Process sale (automatic decrement)
        $adjustment = $inventoryService->decrementForSale($product, $saleQuantity, $saleId);
        
        // Refresh product from database
        $product->refresh();
        
        // Verify inventory was decremented
        $expectedQuantity = $initialQuantity - $saleQuantity;
        expect($product->inventory_quantity)->toBe($expectedQuantity);
        
        // Verify available inventory calculation
        $availableAfterSale = $inventoryService->getAvailableQuantity($product);
        expect($availableAfterSale)->toBe($expectedQuantity);
        
        // Verify audit trail was created
        expect($adjustment)->toBeInstanceOf(InventoryAdjustment::class);
        expect($adjustment->adjustable_type)->toBe(Product::class);
        expect($adjustment->adjustable_id)->toBe($product->id);
        expect($adjustment->adjustment_quantity)->toBe(-$saleQuantity);
        expect($adjustment->reason)->toBe('Sale');
        expect($adjustment->reference_type)->toBe('sale');
        expect($adjustment->reference_id)->toBe($saleId);
        expect($adjustment->quantity_before)->toBe($initialQuantity);
        expect($adjustment->quantity_after)->toBe($expectedQuantity);
    }
});

it('automatically decrements inventory for variation sales', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Variation sales should automatically decrement variation inventory
    for ($i = 0; $i < 100; $i++) {
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
        ]);
        
        $initialQuantity = fake()->numberBetween(50, 1000);
        $saleQuantity = fake()->numberBetween(1, min(50, $initialQuantity));
        $saleId = fake()->uuid();
        
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);
        
        // Record initial available inventory
        $initialAvailable = $inventoryService->getAvailableQuantity($variation);
        expect($initialAvailable)->toBe($initialQuantity);
        
        // Process sale (automatic decrement)
        $adjustment = $inventoryService->decrementForSale($variation, $saleQuantity, $saleId);
        
        // Refresh variation from database
        $variation->refresh();
        
        // Verify inventory was decremented
        $expectedQuantity = $initialQuantity - $saleQuantity;
        expect($variation->inventory_quantity)->toBe($expectedQuantity);
        
        // Verify available inventory calculation
        $availableAfterSale = $inventoryService->getAvailableQuantity($variation);
        expect($availableAfterSale)->toBe($expectedQuantity);
        
        // Verify audit trail was created
        expect($adjustment)->toBeInstanceOf(InventoryAdjustment::class);
        expect($adjustment->adjustable_type)->toBe(ProductVariation::class);
        expect($adjustment->adjustable_id)->toBe($variation->id);
        expect($adjustment->adjustment_quantity)->toBe(-$saleQuantity);
        expect($adjustment->reason)->toBe('Sale');
        expect($adjustment->reference_type)->toBe('sale');
        expect($adjustment->reference_id)->toBe($saleId);
        expect($adjustment->quantity_before)->toBe($initialQuantity);
        expect($adjustment->quantity_after)->toBe($expectedQuantity);
    }
});

it('handles multiple sales correctly', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Multiple sales should cumulatively decrement inventory
    for ($i = 0; $i < 50; $i++) {
        $initialQuantity = fake()->numberBetween(100, 1000);
        
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);
        
        $totalSold = 0;
        $saleCount = fake()->numberBetween(2, 5);
        
        // Process multiple sales
        for ($j = 0; $j < $saleCount; $j++) {
            $saleQuantity = fake()->numberBetween(1, 20);
            $saleId = fake()->uuid();
            
            // Skip if would result in negative inventory
            if ($totalSold + $saleQuantity > $initialQuantity) {
                continue;
            }
            
            $quantityBefore = $product->inventory_quantity;
            
            // Process sale
            $adjustment = $inventoryService->decrementForSale($product, $saleQuantity, $saleId);
            
            // Refresh product
            $product->refresh();
            
            $totalSold += $saleQuantity;
            $expectedQuantity = $initialQuantity - $totalSold;
            
            // Verify inventory was decremented correctly
            expect($product->inventory_quantity)->toBe($expectedQuantity);
            
            // Verify audit trail
            expect($adjustment->quantity_before)->toBe($quantityBefore);
            expect($adjustment->quantity_after)->toBe($expectedQuantity);
            expect($adjustment->adjustment_quantity)->toBe(-$saleQuantity);
            expect($adjustment->reference_id)->toBe($saleId);
        }
        
        // Verify final inventory state
        $finalAvailable = $inventoryService->getAvailableQuantity($product);
        expect($finalAvailable)->toBe($initialQuantity - $totalSold);
    }
});

it('prevents negative inventory from sales', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Sales should not result in negative inventory
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(1, 50);
        $saleQuantity = fake()->numberBetween($initialQuantity, $initialQuantity + 50); // Potentially more than available
        $saleId = fake()->uuid();
        
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);
        
        // Process sale (should handle negative gracefully)
        $adjustment = $inventoryService->decrementForSale($product, $saleQuantity, $saleId);
        
        // Refresh product from database
        $product->refresh();
        
        // Verify inventory never goes below zero
        expect($product->inventory_quantity)->toBeGreaterThanOrEqual(0);
        
        // Verify audit trail records the actual adjustment
        expect($adjustment->quantity_after)->toBeGreaterThanOrEqual(0);
        expect($adjustment->quantity_before)->toBe($initialQuantity);
        
        // If sale quantity exceeded available, inventory should be zero
        if ($saleQuantity >= $initialQuantity) {
            expect($product->inventory_quantity)->toBe(0);
            expect($adjustment->quantity_after)->toBe(0);
        }
    }
});

it('handles returns correctly by incrementing inventory', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Returns should increment inventory (opposite of sales)
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(10, 100);
        $returnQuantity = fake()->numberBetween(1, 50);
        $returnId = fake()->uuid();
        
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);
        
        // Record initial available inventory
        $initialAvailable = $inventoryService->getAvailableQuantity($product);
        expect($initialAvailable)->toBe($initialQuantity);
        
        // Process return (automatic increment)
        $adjustment = $inventoryService->incrementForReturn($product, $returnQuantity, $returnId);
        
        // Refresh product from database
        $product->refresh();
        
        // Verify inventory was incremented
        $expectedQuantity = $initialQuantity + $returnQuantity;
        expect($product->inventory_quantity)->toBe($expectedQuantity);
        
        // Verify available inventory calculation
        $availableAfterReturn = $inventoryService->getAvailableQuantity($product);
        expect($availableAfterReturn)->toBe($expectedQuantity);
        
        // Verify audit trail was created
        expect($adjustment)->toBeInstanceOf(InventoryAdjustment::class);
        expect($adjustment->adjustable_type)->toBe(Product::class);
        expect($adjustment->adjustable_id)->toBe($product->id);
        expect($adjustment->adjustment_quantity)->toBe($returnQuantity);
        expect($adjustment->reason)->toBe('Return');
        expect($adjustment->reference_type)->toBe('return');
        expect($adjustment->reference_id)->toBe($returnId);
        expect($adjustment->quantity_before)->toBe($initialQuantity);
        expect($adjustment->quantity_after)->toBe($expectedQuantity);
    }
});

it('handles sales with reserved inventory correctly', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Sales should work correctly even with reserved inventory
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(100, 1000);
        $reservedQuantity = fake()->numberBetween(10, 50);
        $saleQuantity = fake()->numberBetween(1, 30);
        $saleId = fake()->uuid();
        
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => $reservedQuantity,
        ]);
        
        // Record initial state
        $initialAvailable = $inventoryService->getAvailableQuantity($product);
        expect($initialAvailable)->toBe($initialQuantity - $reservedQuantity);
        
        // Process sale
        $adjustment = $inventoryService->decrementForSale($product, $saleQuantity, $saleId);
        
        // Refresh product from database
        $product->refresh();
        
        // Verify inventory was decremented but reserved quantity unchanged
        $expectedQuantity = max(0, $initialQuantity - $saleQuantity);
        expect($product->inventory_quantity)->toBe($expectedQuantity);
        expect($product->reserved_quantity)->toBe($reservedQuantity); // Should not change
        
        // Verify available inventory calculation
        $availableAfterSale = $inventoryService->getAvailableQuantity($product);
        $expectedAvailable = max(0, $expectedQuantity - $reservedQuantity);
        expect($availableAfterSale)->toBe($expectedAvailable);
        
        // Verify audit trail
        expect($adjustment->quantity_before)->toBe($initialQuantity);
        expect($adjustment->quantity_after)->toBe($expectedQuantity);
        expect($adjustment->adjustment_quantity)->toBe(-$saleQuantity);
    }
});

it('ignores sales for non-tracked inventory items', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Non-tracked items should not be affected by sales
    for ($i = 0; $i < 100; $i++) {
        $initialQuantity = fake()->numberBetween(10, 100);
        $saleQuantity = fake()->numberBetween(1, 50);
        $saleId = fake()->uuid();
        
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => false, // Not tracking inventory
            'inventory_quantity' => $initialQuantity,
            'reserved_quantity' => 0,
        ]);
        
        // Attempt to process sale - should throw exception
        expect(fn() => $inventoryService->decrementForSale($product, $saleQuantity, $saleId))
            ->toThrow(\InvalidArgumentException::class);
        
        // Verify inventory was not changed
        $product->refresh();
        expect($product->inventory_quantity)->toBe($initialQuantity);
        
        // Verify no audit trail was created
        $adjustments = InventoryAdjustment::where('adjustable_type', Product::class)
            ->where('adjustable_id', $product->id)
            ->count();
        expect($adjustments)->toBe(0);
    }
});