<?php

declare(strict_types=1);

use App\Enums\QuoteDiscountType;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\ProductVariation;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('product has required fillable fields', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Test Product',
        'sku' => 'TEST-SKU-001',
        'part_number' => 'PART-001',
        'manufacturer' => 'ACME Corp',
        'product_type' => 'stocked',
        'status' => 'active',
        'lifecycle_stage' => 'released',
        'description' => 'Test description',
        'price' => 99.99,
        'cost_price' => 20,
        'currency_code' => 'USD',
        'is_active' => true,
        'is_bundle' => false,
        'track_inventory' => true,
        'inventory_quantity' => 100,
    ]);

    expect($product->name)->toBe('Test Product')
        ->and($product->sku)->toBe('TEST-SKU-001')
        ->and($product->part_number)->toBe('PART-001')
        ->and($product->manufacturer)->toBe('ACME Corp')
        ->and($product->product_type)->toBe('stocked')
        ->and($product->status)->toBe('active')
        ->and($product->lifecycle_stage)->toBe('released')
        ->and($product->description)->toBe('Test description')
        ->and((float) $product->price)->toBe(99.99)
        ->and((float) $product->cost_price)->toBe(20.00)
        ->and($product->currency_code)->toBe('USD')
        ->and($product->is_active)->toBeTrue()
        ->and($product->is_bundle)->toBeFalse()
        ->and($product->track_inventory)->toBeTrue()
        ->and($product->inventory_quantity)->toBe(100);
});

test('product generates unique slug on creation', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product1 = Product::factory()->create([
        'team_id' => $team->id,
    ]);

    $product2 = Product::factory()->create([
        'team_id' => $team->id,
    ]);

    expect($product1->slug)->not->toBe($product2->slug)
        ->and($product1->slug)->not->toBeEmpty()
        ->and($product2->slug)->not->toBeEmpty();
});

test('product belongs to many categories', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $category1 = ProductCategory::factory()->create(['team_id' => $team->id, 'name' => 'Electronics']);
    $category2 = ProductCategory::factory()->create(['team_id' => $team->id, 'name' => 'Computers']);

    $product->categories()->attach([$category1->id, $category2->id]);

    expect($product->categories)->toHaveCount(2)
        ->and($product->categories->pluck('name')->toArray())->toContain('Electronics', 'Computers');
});

test('product has many attribute assignments', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $attribute = ProductAttribute::factory()->create(['team_id' => $team->id, 'name' => 'Color']);
    $attributeValue = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $attribute->id,
        'value' => 'Red',
    ]);

    $assignment = ProductAttributeAssignment::factory()->create([
        'product_id' => $product->id,
        'product_attribute_id' => $attribute->id,
        'product_attribute_value_id' => $attributeValue->id,
    ]);

    expect($product->attributeAssignments)->toHaveCount(1)
        ->and($product->attributeAssignments->first()->attribute->name)->toBe('Color')
        ->and($product->attributeAssignments->first()->attributeValue->value)->toBe('Red');
});

test('product has many variations', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    $variation1 = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'name' => 'Small',
        'sku' => 'TEST-S',
        'price' => 49.99,
    ]);

    $variation2 = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'name' => 'Large',
        'sku' => 'TEST-L',
        'price' => 69.99,
    ]);

    expect($product->variations)->toHaveCount(2)
        ->and($product->variations->pluck('name')->toArray())->toContain('Small', 'Large');
});

test('product calculates available inventory from variations', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create([
        'team_id' => $team->id,
        'track_inventory' => true,
        'inventory_quantity' => 50,
    ]);

    // Without variations, returns product inventory
    expect($product->availableInventory())->toBe(50);

    // With variations, sums variation inventory
    ProductVariation::factory()->create([
        'product_id' => $product->id,
        'inventory_quantity' => 20,
    ]);

    ProductVariation::factory()->create([
        'product_id' => $product->id,
        'inventory_quantity' => 30,
    ]);

    expect($product->availableInventory())->toBe(50);
});

test('product detects if it has variants', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    expect($product->hasVariants())->toBeFalse();

    ProductVariation::factory()->create(['product_id' => $product->id]);

    expect($product->fresh()->hasVariants())->toBeTrue();
});

test('product has default currency', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create([
        'team_id' => $team->id,
    ]);

    expect($product->currency_code)->toBe('USD');
});

test('product resolves tiered pricing and best discount', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create([
        'team_id' => $team->id,
        'price' => 100,
        'currency_code' => 'USD',
    ]);

    $product->priceTiers()->create([
        'min_quantity' => 10,
        'price' => 90,
        'currency_code' => 'USD',
        'starts_at' => now()->subDay(),
        'team_id' => $team->id,
    ]);

    $product->discountRules()->create([
        'name' => 'Volume Discount',
        'discount_type' => QuoteDiscountType::PERCENT,
        'discount_value' => 10,
        'min_quantity' => 5,
        'starts_at' => now()->subDay(),
        'is_active' => true,
        'team_id' => $team->id,
    ]);

    $product->discountRules()->create([
        'name' => 'Seasonal',
        'discount_type' => QuoteDiscountType::FIXED,
        'discount_value' => 5,
        'min_quantity' => 1,
        'starts_at' => now()->subDay(),
        'is_active' => true,
        'priority' => 1,
        'team_id' => $team->id,
    ]);

    expect($product->priceFor(1))->toBe(95.0) // $100 with $5 fixed discount
        ->and($product->priceFor(6))->toBe(90.0) // 10% off list (best discount)
        ->and($product->priceFor(12))->toBe(81.0); // tiered price (90) with 10% discount
});

test('product lifecycle gating determines sellable status', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create([
        'team_id' => $team->id,
        'status' => 'inactive',
        'lifecycle_stage' => 'draft',
        'is_active' => false,
    ]);

    expect($product->isSellable())->toBeFalse();

    $product->update([
        'status' => 'active',
        'lifecycle_stage' => 'released',
        'is_active' => true,
    ]);

    expect($product->fresh()->isSellable())->toBeTrue();
});

test('product relationships are organized by type', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $crossSell = Product::factory()->create(['team_id' => $team->id]);
    $upsell = Product::factory()->create(['team_id' => $team->id]);
    $bundle = Product::factory()->create(['team_id' => $team->id]);

    $product->relationships()->create([
        'related_product_id' => $crossSell->id,
        'relationship_type' => 'cross_sell',
        'quantity' => 1,
        'team_id' => $team->id,
    ]);

    $product->relationships()->create([
        'related_product_id' => $upsell->id,
        'relationship_type' => 'upsell',
        'quantity' => 1,
        'team_id' => $team->id,
    ]);

    $product->relationships()->create([
        'related_product_id' => $bundle->id,
        'relationship_type' => 'bundle',
        'quantity' => 2,
        'team_id' => $team->id,
    ]);

    expect($product->crossSells()->count())->toBe(1)
        ->and($product->upsells()->count())->toBe(1)
        ->and($product->bundleComponents()->count())->toBe(1);
});

test('product soft deletes', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $productId = $product->id;

    $product->delete();

    expect(Product::find($productId))->toBeNull()
        ->and(Product::withTrashed()->find($productId))->not->toBeNull();
});
