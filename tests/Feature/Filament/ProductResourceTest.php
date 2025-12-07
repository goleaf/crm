<?php

declare(strict_types=1);

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('product resource can render list page', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    Product::factory()->count(3)->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\ListProducts::class)
        ->assertSuccessful();
});

test('product resource can render create page', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    Livewire::test(ProductResource\Pages\CreateProduct::class)
        ->assertSuccessful();
});

test('product resource can create product', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $category = ProductCategory::factory()->create(['team_id' => $team->id]);

    $newData = [
        'name' => 'New Product',
        'sku' => 'NEW-SKU-001',
        'product_category_id' => $category->id,
        'price' => 149.99,
        'currency_code' => 'USD',
        'status' => 'active',
        'description' => 'Test product description',
    ];

    Livewire::test(ProductResource\Pages\CreateProduct::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'New Product',
        'sku' => 'NEW-SKU-001',
        'team_id' => $team->id,
    ]);
});

test('product resource validates required fields', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    Livewire::test(ProductResource\Pages\CreateProduct::class)
        ->fillForm([
            'name' => '',
            'price' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'price']);
});

test('product resource can render edit page', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\EditProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('product resource can update product', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    $newData = [
        'name' => 'Updated Product Name',
        'price' => 199.99,
    ];

    Livewire::test(ProductResource\Pages\EditProduct::class, ['record' => $product->id])
        ->fillForm($newData)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->name)->toBe('Updated Product Name')
        ->and((float) $product->fresh()->price)->toBe(199.99);
});

test('product resource can render view page', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('product resource can delete product', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\EditProduct::class, ['record' => $product->id])
        ->callAction(DeleteAction::class);

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

test('product resource filters by team', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $user = User::factory()->create();
    $team1->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team1);

    $product1 = Product::factory()->create(['team_id' => $team1->id, 'name' => 'Team 1 Product']);
    $product2 = Product::factory()->create(['team_id' => $team2->id, 'name' => 'Team 2 Product']);

    Livewire::test(ProductResource\Pages\ListProducts::class)
        ->assertCanSeeTableRecords([$product1])
        ->assertCanNotSeeTableRecords([$product2]);
});

test('product resource displays categories relation manager', function (): void {
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
        ->assertSuccessful();
});

test('product resource displays variations relation manager', function (): void {
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
        ->assertSuccessful();
});

test('product resource displays attribute assignments relation manager', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\RelationManagers\AttributeAssignmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->assertSuccessful();
});
