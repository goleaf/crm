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

// Feature: products-inventory, Property 14: Inventory adjustment audit trail
// For any inventory quantity change, the system should record timestamp, user ID, and reason, creating a complete audit trail.
// Validates: Requirements 5.2

it('creates complete audit trail for product inventory adjustments', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: For any inventory adjustment, audit trail should be complete
    for ($i = 0; $i < 100; $i++) {
        // Generate random test data
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => fake()->numberBetween(10, 100),
        ]);
        
        $adjustmentQuantity = fake()->numberBetween(-50, 50);
        $reason = fake()->randomElement(['Manual adjustment', 'Stock take', 'Damage', 'Return', 'Sale']);
        $notes = fake()->optional()->sentence();
        $referenceType = fake()->optional()->randomElement(['sale', 'return', 'manual']);
        $referenceId = fake()->optional()->uuid();
        
        $quantityBefore = $product->inventory_quantity;
        
        // Perform adjustment
        $adjustment = $inventoryService->adjustInventory(
            $product,
            $adjustmentQuantity,
            $reason,
            $notes,
            $referenceType,
            $referenceId
        );
        
        // Verify audit trail completeness
        expect($adjustment)->toBeInstanceOf(InventoryAdjustment::class);
        expect($adjustment->team_id)->toBe($team->id);
        expect($adjustment->adjustable_type)->toBe(Product::class);
        expect($adjustment->adjustable_id)->toBe($product->id);
        expect($adjustment->user_id)->toBe($user->id);
        expect($adjustment->quantity_before)->toBe($quantityBefore);
        expect($adjustment->quantity_after)->toBe(max(0, $quantityBefore + $adjustmentQuantity));
        expect($adjustment->adjustment_quantity)->toBe($adjustmentQuantity);
        expect($adjustment->reason)->toBe($reason);
        expect($adjustment->notes)->toBe($notes);
        expect($adjustment->reference_type)->toBe($referenceType);
        expect($adjustment->reference_id)->toBe($referenceId);
        expect($adjustment->created_at)->not->toBeNull();
        expect($adjustment->updated_at)->not->toBeNull();
        
        // Verify audit record is persisted in database
        $persistedAdjustment = InventoryAdjustment::find($adjustment->id);
        expect($persistedAdjustment)->not->toBeNull();
        expect($persistedAdjustment->team_id)->toBe($team->id);
        expect($persistedAdjustment->user_id)->toBe($user->id);
        expect($persistedAdjustment->reason)->toBe($reason);
    }
});

it('creates complete audit trail for variation inventory adjustments', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: For any variation inventory adjustment, audit trail should be complete
    for ($i = 0; $i < 100; $i++) {
        // Generate random test data
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
        ]);
        
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'track_inventory' => true,
            'inventory_quantity' => fake()->numberBetween(10, 100),
        ]);
        
        $adjustmentQuantity = fake()->numberBetween(-50, 50);
        $reason = fake()->randomElement(['Manual adjustment', 'Stock take', 'Damage', 'Return', 'Sale']);
        $notes = fake()->optional()->sentence();
        $referenceType = fake()->optional()->randomElement(['sale', 'return', 'manual']);
        $referenceId = fake()->optional()->uuid();
        
        $quantityBefore = $variation->inventory_quantity;
        
        // Perform adjustment
        $adjustment = $inventoryService->adjustInventory(
            $variation,
            $adjustmentQuantity,
            $reason,
            $notes,
            $referenceType,
            $referenceId
        );
        
        // Verify audit trail completeness
        expect($adjustment)->toBeInstanceOf(InventoryAdjustment::class);
        expect($adjustment->team_id)->toBe($team->id);
        expect($adjustment->adjustable_type)->toBe(ProductVariation::class);
        expect($adjustment->adjustable_id)->toBe($variation->id);
        expect($adjustment->user_id)->toBe($user->id);
        expect($adjustment->quantity_before)->toBe($quantityBefore);
        expect($adjustment->quantity_after)->toBe(max(0, $quantityBefore + $adjustmentQuantity));
        expect($adjustment->adjustment_quantity)->toBe($adjustmentQuantity);
        expect($adjustment->reason)->toBe($reason);
        expect($adjustment->notes)->toBe($notes);
        expect($adjustment->reference_type)->toBe($referenceType);
        expect($adjustment->reference_id)->toBe($referenceId);
        expect($adjustment->created_at)->not->toBeNull();
        expect($adjustment->updated_at)->not->toBeNull();
        
        // Verify audit record is persisted in database
        $persistedAdjustment = InventoryAdjustment::find($adjustment->id);
        expect($persistedAdjustment)->not->toBeNull();
        expect($persistedAdjustment->team_id)->toBe($team->id);
        expect($persistedAdjustment->user_id)->toBe($user->id);
        expect($persistedAdjustment->reason)->toBe($reason);
    }
});

it('maintains audit trail chronological order', function () {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    
    $this->actingAs($user);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Audit trail should maintain chronological order
    for ($i = 0; $i < 50; $i++) {
        $product = Product::factory()->create([
            'team_id' => $team->id,
            'track_inventory' => true,
            'inventory_quantity' => 100,
        ]);
        
        $adjustments = [];
        
        // Make multiple adjustments
        for ($j = 0; $j < fake()->numberBetween(2, 5); $j++) {
            $adjustment = $inventoryService->adjustInventory(
                $product,
                fake()->numberBetween(-10, 10),
                'Test adjustment ' . ($j + 1)
            );
            
            $adjustments[] = $adjustment;
            
            // Small delay to ensure different timestamps
            usleep(1000);
        }
        
        // Verify chronological order
        $history = $inventoryService->getAdjustmentHistory($product);
        
        expect($history->count())->toBeGreaterThanOrEqual(count($adjustments));
        
        // Verify most recent first
        for ($k = 0; $k < $history->count() - 1; $k++) {
            expect($history[$k]->created_at->greaterThanOrEqualTo($history[$k + 1]->created_at))->toBeTrue();
        }
    }
});

it('preserves audit trail integrity across team boundaries', function () {
    // Create test data
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user1->teams()->attach($team1);
    $user2->teams()->attach($team2);
    
    $inventoryService = app(InventoryService::class);
    
    // Property: Audit trail should respect team boundaries
    for ($i = 0; $i < 50; $i++) {
        // Create products for different teams
        $product1 = Product::factory()->create([
            'team_id' => $team1->id,
            'track_inventory' => true,
            'inventory_quantity' => 50,
        ]);
        
        $product2 = Product::factory()->create([
            'team_id' => $team2->id,
            'track_inventory' => true,
            'inventory_quantity' => 50,
        ]);
        
        // Make adjustments as different users
        $this->actingAs($user1);
        $adjustment1 = $inventoryService->adjustInventory($product1, 10, 'Team 1 adjustment');
        
        $this->actingAs($user2);
        $adjustment2 = $inventoryService->adjustInventory($product2, -5, 'Team 2 adjustment');
        
        // Verify team isolation in audit trail
        expect($adjustment1->team_id)->toBe($team1->id);
        expect($adjustment1->user_id)->toBe($user1->id);
        expect($adjustment2->team_id)->toBe($team2->id);
        expect($adjustment2->user_id)->toBe($user2->id);
        
        // Verify team-scoped queries
        $team1Adjustments = InventoryAdjustment::where('team_id', $team1->id)->get();
        $team2Adjustments = InventoryAdjustment::where('team_id', $team2->id)->get();
        
        expect($team1Adjustments->contains($adjustment1))->toBeTrue();
        expect($team1Adjustments->contains($adjustment2))->toBeFalse();
        expect($team2Adjustments->contains($adjustment2))->toBeTrue();
        expect($team2Adjustments->contains($adjustment1))->toBeFalse();
    }
});