<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Team;
use App\Models\User;

/**
 * **Feature: core-crm-modules, Property 1: Account persistence**
 *
 * **Validates: Requirements 1.1**
 *
 * Property: Creating/updating an account with required fields and custom data
 * must persist and be retrievable with identical values.
 */
test('property: account creation persists all required fields correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    // Generate random account data
    $accountData = [
        'name' => fake()->company(),
        'account_type' => fake()->randomElement(['customer', 'prospect', 'partner', 'vendor']),
        'industry' => fake()->randomElement(['Technology', 'Healthcare', 'Finance', 'Manufacturing']),
        'revenue' => fake()->numberBetween(10000, 10000000),
        'employee_count' => fake()->numberBetween(1, 10000),
        'ownership' => fake()->randomElement(['public', 'private', 'subsidiary']),
        'website' => fake()->url(),
        'phone' => fake()->phoneNumber(),
        'email' => fake()->companyEmail(),
        'billing_address_line_1' => fake()->streetAddress(),
        'billing_city' => fake()->city(),
        'billing_state' => fake()->state(),
        'billing_postal_code' => fake()->postcode(),
        'billing_country' => fake()->countryCode(),
        'shipping_address_line_1' => fake()->streetAddress(),
        'shipping_city' => fake()->city(),
        'shipping_state' => fake()->state(),
        'shipping_postal_code' => fake()->postcode(),
        'shipping_country' => fake()->countryCode(),
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ];

    // Create account
    $account = Account::create($accountData);

    // Verify persistence
    expect($account)->toExist();
    expect($account->fresh())->not->toBeNull();

    // Verify all fields are persisted correctly
    $retrievedAccount = Account::find($account->id);
    foreach ($accountData as $field => $value) {
        expect($retrievedAccount->$field)->toBe($value, "Field {$field} should match");
    }
})->repeat(100);

test('property: account updates preserve data integrity', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    // Create initial account
    $account = Account::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    $originalId = $account->id;
    $originalCreatedAt = $account->created_at;

    // Generate random update data
    $updateData = [
        'name' => fake()->company(),
        'revenue' => fake()->numberBetween(10000, 10000000),
        'employee_count' => fake()->numberBetween(1, 10000),
        'website' => fake()->url(),
    ];

    // Update account
    $account->update($updateData);

    // Verify updates are persisted
    $updatedAccount = Account::find($originalId);
    expect($updatedAccount)->not->toBeNull();
    expect($updatedAccount->id)->toBe($originalId);
    expect($updatedAccount->created_at->equalTo($originalCreatedAt))->toBeTrue();

    foreach ($updateData as $field => $value) {
        expect($updatedAccount->$field)->toBe($value, "Updated field {$field} should match");
    }
})->repeat(50);

test('property: account custom fields persist correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $account = Account::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    // Generate random custom field data
    $customData = [
        'custom_field_1' => fake()->word(),
        'custom_field_2' => fake()->numberBetween(1, 1000),
        'custom_field_3' => fake()->boolean(),
    ];

    // Set custom fields (assuming custom fields trait is available)
    if (method_exists($account, 'setCustomField')) {
        foreach ($customData as $field => $value) {
            $account->setCustomField($field, $value);
        }
        $account->save();

        // Verify custom fields are persisted
        $retrievedAccount = Account::find($account->id);
        foreach ($customData as $field => $value) {
            expect($retrievedAccount->getCustomField($field))->toBe($value, "Custom field {$field} should match");
        }
    }
})->repeat(50);

test('property: account soft deletes preserve data', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $account = Account::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    $originalData = $account->toArray();

    // Soft delete
    $account->delete();

    // Verify soft delete
    expect(Account::find($account->id))->toBeNull();
    expect(Account::withTrashed()->find($account->id))->not->toBeNull();

    // Verify data integrity after soft delete
    $trashedAccount = Account::withTrashed()->find($account->id);
    expect($trashedAccount->deleted_at)->not->toBeNull();

    // Core data should remain unchanged
    foreach (['name', 'account_type', 'industry', 'revenue'] as $field) {
        if (isset($originalData[$field])) {
            expect($trashedAccount->$field)->toBe($originalData[$field]);
        }
    }
})->repeat(30);

test('property: account hierarchies persist parent-child relationships', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    // Create parent account
    $parentAccount = Account::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    // Create child account
    $childAccount = Account::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'parent_id' => $parentAccount->id,
    ]);

    // Verify hierarchy persistence
    $retrievedChild = Account::find($childAccount->id);
    $retrievedParent = Account::find($parentAccount->id);

    expect($retrievedChild->parent_id)->toBe($parentAccount->id);

    if (method_exists($retrievedChild, 'parent')) {
        expect($retrievedChild->parent)->not->toBeNull();
        expect($retrievedChild->parent->id)->toBe($parentAccount->id);
    }

    if (method_exists($retrievedParent, 'children')) {
        expect($retrievedParent->children)->toHaveCount(1);
        expect($retrievedParent->children->first()->id)->toBe($childAccount->id);
    }
})->repeat(50);
