<?php

declare(strict_types=1);

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Role;
use App\Models\Taxonomy;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

if (! function_exists('actingAsFilamentAdmin')) {
    /**
     * @return array{0: User, 1: Team}
     */
    function actingAsFilamentAdmin(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->personalTeam();

        expect($team)->not->toBeNull();

        $user->switchTeam($team);
        actingAs($user);

        Filament::setTenant($team);
        setPermissionsTeamId($team->getKey());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin');
        $user->assignRole('admin');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$user, $team];
    }
}

test('categories relation manager can attach category', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);
    $category = Taxonomy::query()->create([
        'team_id' => $team->id,
        'name' => 'Electronics',
        'slug' => 'electronics',
        'type' => 'product_category',
    ]);

    Livewire::test(ProductResource\RelationManagers\CategoriesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('attach', data: [
            'recordId' => $category->id,
        ])
        ->assertHasNoTableActionErrors();

    expect($product->fresh()->taxonomyCategories)->toHaveCount(1)
        ->and($product->fresh()->taxonomyCategories->first()->name)->toBe('Electronics');
});

test('categories relation manager can create new category', function (): void {
    [, $team] = actingAsFilamentAdmin();

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

    $this->assertDatabaseHas('taxonomies', [
        'name' => 'New Category',
        'team_id' => $team->id,
        'type' => 'product_category',
    ]);
});

test('categories relation manager can detach category', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);
    $category = Taxonomy::query()->create([
        'team_id' => $team->id,
        'name' => 'To Detach',
        'slug' => 'to-detach',
        'type' => 'product_category',
    ]);
    $product->taxonomyCategories()->attach($category);

    expect($product->taxonomyCategories)->toHaveCount(1);

    Livewire::test(ProductResource\RelationManagers\CategoriesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->callTableAction('detach', $category)
        ->assertHasNoTableActionErrors();

    expect($product->fresh()->taxonomyCategories)->toHaveCount(0);
});

test('variations relation manager can create variation', function (): void {
    [, $team] = actingAsFilamentAdmin();

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
    [, $team] = actingAsFilamentAdmin();

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
    [, $team] = actingAsFilamentAdmin();

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
    [, $team] = actingAsFilamentAdmin();

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
    [, $team] = actingAsFilamentAdmin();

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
    [, $team] = actingAsFilamentAdmin();

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
        'custom_value' => json_encode('Custom Material Value'),
    ]);
});

test('attribute assignments relation manager can edit assignment', function (): void {
    [, $team] = actingAsFilamentAdmin();

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
    [, $team] = actingAsFilamentAdmin();

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
