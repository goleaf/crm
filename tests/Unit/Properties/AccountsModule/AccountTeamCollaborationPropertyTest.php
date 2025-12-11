<?php

/**
 * Account Team Collaboration Property Tests.
 *
 * This test suite validates the account team collaboration features for the accounts module.
 * It uses property-based testing with randomized inputs to ensure robust coverage across
 * various scenarios and edge cases.
 *
 *
 * @see \App\Models\AccountTeamMember
 * @see \App\Models\Company::accountTeam()
 * @see \App\Models\Company::accountTeamMembers()
 * @see \App\Models\Company::ensureAccountOwnerOnTeam()
 * @see \App\Enums\AccountTeamRole
 * @see \App\Enums\AccountTeamAccessLevel
 *
 * **Feature: accounts-module**
 * - **Property 29:** Account team member assignment
 * - **Property 30:** Account team member removal preserves history
 * - **Property 31:** Account owner team synchronization
 *
 * **Validates Requirements:**
 * - 12.1: Account team member management
 * - 12.2: Role-based access control for account teams
 * - 12.4: Account ownership transfer
 * - 12.5: Team membership history preservation
 *
 * **Properties Tested:**
 * - For any account and user, adding the user to the account team with a specific role
 *   should create an AccountTeamMember record that is queryable from both the account
 *   and user perspectives.
 * - For any account team member, removing them from the account should delete the team
 *   membership while preserving all their historical activities and contributions.
 * - For any account, when the account owner changes, the system should automatically
 *   update the account team to ensure the new owner has Owner role and Manage access level.
 *
 * **Test Categories:**
 * - Property 29 tests: Team member assignment and queryability (2 tests, repeated)
 * - Property 30 tests: Team member removal with history preservation (1 test, repeated)
 * - Property 31 tests: Account owner synchronization and idempotency (4 tests, repeated)
 * - Edge case tests: Null owners, enum validation, relationship loading, cascade deletion (5 tests)
 */

declare(strict_types=1);

use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use App\Models\AccountTeamMember;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;

// Property 29: Account team member assignment
test('property: account team member assignment creates queryable record from both perspectives', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Create a random user to add to the account team
    $collaborator = User::factory()->create();
    $team->users()->attach($collaborator);

    // Pick random role and access level
    $role = fake()->randomElement(AccountTeamRole::cases());
    $accessLevel = fake()->randomElement(AccountTeamAccessLevel::cases());

    // Add user to account team
    $member = AccountTeamMember::create([
        'company_id' => $company->getKey(),
        'team_id' => $team->getKey(),
        'user_id' => $collaborator->getKey(),
        'role' => $role,
        'access_level' => $accessLevel,
    ]);

    // Verify queryable from account perspective
    $company->refresh();
    $accountTeamMember = $company->accountTeamMembers()
        ->where('user_id', $collaborator->getKey())
        ->first();

    expect($accountTeamMember)->not()->toBeNull()
        ->and($accountTeamMember->role)->toBe($role)
        ->and($accountTeamMember->access_level)->toBe($accessLevel);

    // Verify queryable via accountTeam relationship (BelongsToMany)
    $teamUsers = $company->accountTeam()->pluck('users.id')->toArray();
    expect($teamUsers)->toContain($collaborator->getKey());

    // Verify pivot data is accessible
    $pivotUser = $company->accountTeam()
        ->where('users.id', $collaborator->getKey())
        ->first();

    expect($pivotUser)->not()->toBeNull();

    /** @var \Illuminate\Database\Eloquent\Relations\Pivot $pivot */
    $pivot = $pivotUser->pivot;
    expect($pivot->getAttribute('role'))->toBe($role->value)
        ->and($pivot->getAttribute('access_level'))->toBe($accessLevel->value);
})->repeat(10);

// Property 29: Multiple team members can be assigned with different roles
test('property: multiple team members can be assigned with different roles', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Create multiple collaborators
    $collaboratorCount = fake()->numberBetween(2, 5);
    $collaborators = [];

    for ($i = 0; $i < $collaboratorCount; $i++) {
        $collaborator = User::factory()->create();
        $team->users()->attach($collaborator);

        $role = fake()->randomElement(AccountTeamRole::cases());
        $accessLevel = fake()->randomElement(AccountTeamAccessLevel::cases());

        AccountTeamMember::create([
            'company_id' => $company->getKey(),
            'team_id' => $team->getKey(),
            'user_id' => $collaborator->getKey(),
            'role' => $role,
            'access_level' => $accessLevel,
        ]);

        $collaborators[] = [
            'user' => $collaborator,
            'role' => $role,
            'access_level' => $accessLevel,
        ];
    }

    $company->refresh();

    // Verify all collaborators are in the account team
    expect($company->accountTeamMembers()->count())->toBe($collaboratorCount);

    // Verify each collaborator has correct role and access level
    foreach ($collaborators as $collab) {
        $member = $company->accountTeamMembers()
            ->where('user_id', $collab['user']->getKey())
            ->first();

        expect($member)->not()->toBeNull()
            ->and($member->role)->toBe($collab['role'])
            ->and($member->access_level)->toBe($collab['access_level']);
    }
})->repeat(5);

// Property 30: Account team member removal preserves history
test('property: account team member removal deletes membership but preserves user record', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Add a collaborator
    $collaborator = User::factory()->create();
    $team->users()->attach($collaborator);

    $member = AccountTeamMember::create([
        'company_id' => $company->getKey(),
        'team_id' => $team->getKey(),
        'user_id' => $collaborator->getKey(),
        'role' => fake()->randomElement(AccountTeamRole::cases()),
        'access_level' => fake()->randomElement(AccountTeamAccessLevel::cases()),
    ]);

    $memberId = $member->getKey();
    $collaboratorId = $collaborator->getKey();

    // Remove the team member
    $member->delete();

    // Verify membership is deleted
    expect(AccountTeamMember::find($memberId))->toBeNull();

    // Verify user record still exists (history preserved)
    expect(User::find($collaboratorId))->not()->toBeNull();

    // Verify company still exists
    expect(Company::find($company->getKey()))->not()->toBeNull();

    // Verify user is no longer in account team
    $company->refresh();
    expect($company->accountTeamMembers()->where('user_id', $collaboratorId)->exists())->toBeFalse();
})->repeat(10);

// Property 31: Account owner team synchronization
test('property: ensureAccountOwnerOnTeam creates owner with manage access', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Ensure owner is on team
    $company->ensureAccountOwnerOnTeam();
    $company->refresh();

    $ownerMember = $company->accountTeamMembers()
        ->where('user_id', $owner->getKey())
        ->first();

    expect($ownerMember)->not()->toBeNull()
        ->and($ownerMember->role)->toBe(AccountTeamRole::OWNER)
        ->and($ownerMember->access_level)->toBe(AccountTeamAccessLevel::MANAGE)
        ->and($ownerMember->team_id)->toBe($team->getKey());
})->repeat(10);

// Property 31: Changing account owner updates team membership
test('property: changing account owner updates team membership to owner role', function (): void {
    $team = Team::factory()->create();
    $originalOwner = User::factory()->create();
    $newOwner = User::factory()->create();
    $team->users()->attach([$originalOwner->getKey(), $newOwner->getKey()]);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $originalOwner->getKey()]);

    // Ensure original owner is on team
    $company->ensureAccountOwnerOnTeam();

    // Change owner
    $company->update(['account_owner_id' => $newOwner->getKey()]);
    $company->ensureAccountOwnerOnTeam();
    $company->refresh();

    // Verify new owner has OWNER role with MANAGE access
    $newOwnerMember = $company->accountTeamMembers()
        ->where('user_id', $newOwner->getKey())
        ->first();

    expect($newOwnerMember)->not()->toBeNull()
        ->and($newOwnerMember->role)->toBe(AccountTeamRole::OWNER)
        ->and($newOwnerMember->access_level)->toBe(AccountTeamAccessLevel::MANAGE);
})->repeat(10);

// Property 31: ensureAccountOwnerOnTeam is idempotent
test('property: ensureAccountOwnerOnTeam is idempotent', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Call multiple times
    $callCount = fake()->numberBetween(2, 5);
    for ($i = 0; $i < $callCount; $i++) {
        $company->ensureAccountOwnerOnTeam();
    }

    $company->refresh();

    // Should only have one membership record for the owner
    $ownerMemberCount = $company->accountTeamMembers()
        ->where('user_id', $owner->getKey())
        ->count();

    expect($ownerMemberCount)->toBe(1);

    // Verify it still has correct role and access
    $ownerMember = $company->accountTeamMembers()
        ->where('user_id', $owner->getKey())
        ->first();

    expect($ownerMember->role)->toBe(AccountTeamRole::OWNER)
        ->and($ownerMember->access_level)->toBe(AccountTeamAccessLevel::MANAGE);
})->repeat(5);

// Property 31: ensureAccountOwnerOnTeam updates existing membership to owner role
test('property: ensureAccountOwnerOnTeam upgrades existing membership to owner role', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // First add owner with a different role
    AccountTeamMember::create([
        'company_id' => $company->getKey(),
        'team_id' => $team->getKey(),
        'user_id' => $owner->getKey(),
        'role' => AccountTeamRole::SUPPORT,
        'access_level' => AccountTeamAccessLevel::VIEW,
    ]);

    // Now ensure owner is on team (should upgrade)
    $company->ensureAccountOwnerOnTeam();
    $company->refresh();

    // Should still only have one membership
    $ownerMemberCount = $company->accountTeamMembers()
        ->where('user_id', $owner->getKey())
        ->count();

    expect($ownerMemberCount)->toBe(1);

    // Should be upgraded to OWNER with MANAGE
    $ownerMember = $company->accountTeamMembers()
        ->where('user_id', $owner->getKey())
        ->first();

    expect($ownerMember->role)->toBe(AccountTeamRole::OWNER)
        ->and($ownerMember->access_level)->toBe(AccountTeamAccessLevel::MANAGE);
})->repeat(5);

// Property: Account team respects unique constraint
test('property: account team member assignment respects unique constraint per user per company', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    $collaborator = User::factory()->create();
    $team->users()->attach($collaborator);

    // Add collaborator first time
    AccountTeamMember::create([
        'company_id' => $company->getKey(),
        'team_id' => $team->getKey(),
        'user_id' => $collaborator->getKey(),
        'role' => AccountTeamRole::SALES,
        'access_level' => AccountTeamAccessLevel::EDIT,
    ]);

    // Attempting to add same user again should fail due to unique constraint
    $exceptionThrown = false;

    try {
        AccountTeamMember::create([
            'company_id' => $company->getKey(),
            'team_id' => $team->getKey(),
            'user_id' => $collaborator->getKey(),
            'role' => AccountTeamRole::SUPPORT,
            'access_level' => AccountTeamAccessLevel::VIEW,
        ]);
    } catch (\Illuminate\Database\QueryException) {
        $exceptionThrown = true;
    }

    expect($exceptionThrown)->toBeTrue();

    // Should still only have one membership for this user
    $memberCount = $company->accountTeamMembers()
        ->where('user_id', $collaborator->getKey())
        ->count();

    expect($memberCount)->toBe(1);
})->repeat(5);

// Edge case: Account team member with null account_owner_id
test('property: ensureAccountOwnerOnTeam handles null account_owner_id gracefully', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => null]);

    // Should not throw exception
    $company->ensureAccountOwnerOnTeam();
    $company->refresh();

    // Should have no account team members since there's no owner
    expect($company->accountTeamMembers()->count())->toBe(0);
})->repeat(10);

// Edge case: Verify all enum values are valid for role assignment
test('property: all AccountTeamRole enum values can be assigned', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    foreach (AccountTeamRole::cases() as $role) {
        $collaborator = User::factory()->create();
        $team->users()->attach($collaborator);

        $member = AccountTeamMember::create([
            'company_id' => $company->getKey(),
            'team_id' => $team->getKey(),
            'user_id' => $collaborator->getKey(),
            'role' => $role,
            'access_level' => AccountTeamAccessLevel::VIEW,
        ]);

        expect($member->role)->toBe($role)
            ->and($member->role->value)->toBe($role->value);
    }
});

// Edge case: Verify all enum values are valid for access level assignment
test('property: all AccountTeamAccessLevel enum values can be assigned', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    foreach (AccountTeamAccessLevel::cases() as $accessLevel) {
        $collaborator = User::factory()->create();
        $team->users()->attach($collaborator);

        $member = AccountTeamMember::create([
            'company_id' => $company->getKey(),
            'team_id' => $team->getKey(),
            'user_id' => $collaborator->getKey(),
            'role' => AccountTeamRole::SALES,
            'access_level' => $accessLevel,
        ]);

        expect($member->access_level)->toBe($accessLevel)
            ->and($member->access_level->value)->toBe($accessLevel->value);
    }
});

// Edge case: AccountTeamMember relationships are correctly loaded
test('property: AccountTeamMember relationships are correctly loaded', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    $collaborator = User::factory()->create();
    $team->users()->attach($collaborator);

    $member = AccountTeamMember::create([
        'company_id' => $company->getKey(),
        'team_id' => $team->getKey(),
        'user_id' => $collaborator->getKey(),
        'role' => fake()->randomElement(AccountTeamRole::cases()),
        'access_level' => fake()->randomElement(AccountTeamAccessLevel::cases()),
    ]);

    // Reload with relationships
    $member->refresh();
    $member->load(['company', 'user', 'team']);

    expect($member->company)->not()->toBeNull()
        ->and($member->company->getKey())->toBe($company->getKey())
        ->and($member->user)->not()->toBeNull()
        ->and($member->user->getKey())->toBe($collaborator->getKey())
        ->and($member->team)->not()->toBeNull()
        ->and($member->team->getKey())->toBe($team->getKey());
})->repeat(5);

// Edge case: Deleting company cascades to account team members
test('property: deleting company removes associated account team members', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $company = Company::factory()
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Add some team members
    $memberCount = fake()->numberBetween(2, 5);
    for ($i = 0; $i < $memberCount; $i++) {
        $collaborator = User::factory()->create();
        $team->users()->attach($collaborator);

        AccountTeamMember::create([
            'company_id' => $company->getKey(),
            'team_id' => $team->getKey(),
            'user_id' => $collaborator->getKey(),
            'role' => fake()->randomElement(AccountTeamRole::cases()),
            'access_level' => fake()->randomElement(AccountTeamAccessLevel::cases()),
        ]);
    }

    $companyId = $company->getKey();

    // Verify members exist
    expect(AccountTeamMember::where('company_id', $companyId)->count())->toBe($memberCount);

    // Delete company (force delete to bypass soft deletes if present)
    $company->forceDelete();

    // Verify members are deleted (cascade)
    expect(AccountTeamMember::where('company_id', $companyId)->count())->toBe(0);
})->repeat(3);
