<?php

declare(strict_types=1);

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('categories relation manager can attach category', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $category = ProductCategory::factory()->create(['team_id' => $team->id, 'name' => 'Electronics']);

    Livewire::test(ProductResource\RelationManagers\CategoriesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('attach', data: [
            'recordId' => $category->id,
        ])
        ->assertHasNoTableActionErrors();

    expect($product->fresh()->categories)->toHaveCount(1)
        ->and($product->fresh()->categories->first()->name)->toBe('Electronics');
});

test('categories relation manager can create new category', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\RelationManagers\CategoriesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('create', data: [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'Test description',
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('product_categories', [
        'name' => 'New Category',
        'team_id' => $team->id,
    ]);
});

test('categories relation manager can detach category', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $category = ProductCategory::factory()->create(['team_id' => $team->id]);
    $product->categories()->attach($category);

    expect($product->categories)->toHaveCount(1);

    Livewire::test(ProductResource\RelationManagers\CategoriesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('detach', $category)
        ->assertHasNoTableActionErrors();

    expect($product->fresh()->categories)->toHaveCount(0);
});

test('variations relation manager can create variation', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\RelationManagers\VariationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('create', data: [
            'name' => 'Small Size',
            'sku' => 'PROD-S',
            'price' => 49.99,
            'currency_code' => 'USD',
            'is_default' => false,
            'track_inventory' => true,
            'inventory_quantity' => 100,
            'options' => ['size' => 'Small'],
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('product_variations', [
        'product_id' => $product->id,
        'name' => 'Small Size',
        'sku' => 'PROD-S',
    ]);
});

test('variations relation manager validates required fields', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\RelationManagers\VariationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('create', data: [
            'name' => '',
            'sku' => '',
            'price' => null,
        ])
        ->assertHasTableActionErrors(['name', 'sku', 'price']);
});

test('variations relation manager can edit variation', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $variation = $product->variations()->create([
        'name' => 'Original Name',
        'sku' => 'ORIG-SKU',
        'price' => 99.99,
        'currency_code' => 'USD',
    ]);

    Livewire::test(ProductResource\RelationManagers\VariationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('edit', $variation, data: [
            'name' => 'Updated Name',
            'price' => 149.99,
        ])
        ->assertHasNoTableActionErrors();

    expect($variation->fresh()->name)->toBe('Updated Name')
        ->and((float) $variation->fresh()->price)->toBe(149.99);
});

test('variations relation manager can delete variation', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $variation = $product->variations()->create([
        'name' => 'To Delete',
        'sku' => 'DEL-SKU',
        'price' => 99.99,
        'currency_code' => 'USD',
    ]);

    Livewire::test(ProductResource\RelationManagers\VariationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('delete', $variation)
        ->assertHasNoTableActionErrors();

    $this->assertSoftDeleted('product_variations', ['id' => $variation->id]);
});

test('attribute assignments relation manager can create assignment', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $attribute = ProductAttribute::factory()->create(['team_id' => $team->id, 'name' => 'Color']);
    $attributeValue = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $attribute->id,
        'value' => 'Blue',
    ]);

    Livewire::test(ProductResource\RelationManagers\AttributeAssignmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('create', data: [
            'product_attribute_id' => $attribute->id,
            'product_attribute_value_id' => $attributeValue->id,
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('product_attribute_assignments', [
        'product_id' => $product->id,
        'product_attribute_id' => $attribute->id,
        'product_attribute_value_id' => $attributeValue->id,
    ]);
});

test('attribute assignments relation manager can use custom value', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $attribute = ProductAttribute::factory()->create(['team_id' => $team->id, 'name' => 'Material']);

    Livewire::test(ProductResource\RelationManagers\AttributeAssignmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('create', data: [
            'product_attribute_id' => $attribute->id,
            'custom_value' => 'Custom Material Value',
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('product_attribute_assignments', [
        'product_id' => $product->id,
        'product_attribute_id' => $attribute->id,
        'custom_value' => 'Custom Material Value',
    ]);
});

test('attribute assignments relation manager can edit assignment', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $attribute = ProductAttribute::factory()->create(['team_id' => $team->id]);
    $assignment = $product->attributeAssignments()->create([
        'product_attribute_id' => $attribute->id,
        'custom_value' => 'Original Value',
    ]);

    Livewire::test(ProductResource\RelationManagers\AttributeAssignmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('edit', $assignment, data: [
            'custom_value' => 'Updated Value',
        ])
        ->assertHasNoTableActionErrors();

    expect($assignment->fresh()->custom_value)->toBe('Updated Value');
});

test('attribute assignments relation manager can delete assignment', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);
    $attribute = ProductAttribute::factory()->create(['team_id' => $team->id]);
    $assignment = $product->attributeAssignments()->create([
        'product_attribute_id' => $attribute->id,
        'custom_value' => 'To Delete',
    ]);

    Livewire::test(ProductResource\RelationManagers\AttributeAssignmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('delete', $assignment)
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseMissing('product_attribute_assignments', ['id' => $assignment->id]);
});
