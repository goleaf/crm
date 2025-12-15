<?php

declare(strict_types=1);

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Role;
use App\Models\Taxonomy;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Team}
 */
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

test('product resource can render list page', function (): void {
    [, $team] = actingAsFilamentAdmin();

    Product::factory()->count(3)->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\ListProducts::class)
        ->assertSuccessful();
});

test('product resource can render create page', function (): void {
    actingAsFilamentAdmin();

    Livewire::test(ProductResource\Pages\CreateProduct::class)
        ->assertSuccessful();
});

test('product resource can create product', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $category = Taxonomy::query()->create([
        'team_id' => $team->id,
        'name' => 'Electronics',
        'slug' => 'electronics',
        'type' => 'product_category',
    ]);

    $newData = [
        'name' => 'New Product',
        'sku' => 'NEW-SKU-001',
        'taxonomyCategories' => [$category->id],
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
    actingAsFilamentAdmin();

    Livewire::test(ProductResource\Pages\CreateProduct::class)
        ->fillForm([
            'name' => '',
            'price' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'price']);
});

test('product resource can render edit page', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\EditProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('product resource can update product', function (): void {
    [, $team] = actingAsFilamentAdmin();

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
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('product resource can delete product', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\Pages\ViewProduct::class, ['record' => $product->id])
        ->callAction(DeleteAction::class);

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

test('product resource filters by team', function (): void {
    [$user, $team1] = actingAsFilamentAdmin();
    $team2 = Team::factory()->create();
    $team2->users()->attach($user);

    $product1 = Product::factory()->create(['team_id' => $team1->id, 'name' => 'Team 1 Product']);
    $product2 = Product::factory()->create(['team_id' => $team2->id, 'name' => 'Team 2 Product']);

    Livewire::test(ProductResource\Pages\ListProducts::class)
        ->assertCanSeeTableRecords([$product1])
        ->assertCanNotSeeTableRecords([$product2]);
});

test('product resource displays categories relation manager', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\RelationManagers\CategoriesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->assertSuccessful();
});

test('product resource displays variations relation manager', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\RelationManagers\VariationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->assertSuccessful();
});

test('product resource displays attribute assignments relation manager', function (): void {
    [, $team] = actingAsFilamentAdmin();

    $product = Product::factory()->create(['team_id' => $team->id]);

    Livewire::test(ProductResource\RelationManagers\AttributeAssignmentsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ProductResource\Pages\ViewProduct::class,
    ])
        ->assertSuccessful();
});
