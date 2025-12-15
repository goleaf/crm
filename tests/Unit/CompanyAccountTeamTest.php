<?php

declare(strict_types=1);

use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use App\Models\Company;
use App\Models\User;

it('adds the account owner to the account team with manage access', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->personalTeam();

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
        ]);

    $company->ensureAccountOwnerOnTeam();
    $company->refresh();

    $member = $company->accountTeamMembers()->where('user_id', $owner->getKey())->first();

    expect($member)->not()->toBeNull()
        ->and($member->role)->toBe(AccountTeamRole::OWNER)
        ->and($member->access_level)->toBe(AccountTeamAccessLevel::MANAGE)
        ->and($member->team_id)->toBe($team->getKey());
});

it('updates the owner membership instead of duplicating it', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->personalTeam();

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
        ]);

    $company->accountTeamMembers()
        ->where('user_id', $owner->getKey())
        ->update([
            'role' => AccountTeamRole::SUPPORT,
            'access_level' => AccountTeamAccessLevel::VIEW,
        ]);

    $company->ensureAccountOwnerOnTeam();

    $member = $company->accountTeamMembers()->where('user_id', $owner->getKey())->first();

    expect($company->accountTeamMembers()->where('user_id', $owner->getKey())->count())->toBe(1)
        ->and($member->role)->toBe(AccountTeamRole::OWNER)
        ->and($member->access_level)->toBe(AccountTeamAccessLevel::MANAGE);
});

it('exposes collaborators through the accountTeam relationship', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->personalTeam();
    $collaborator = User::factory()->create();

    $team->users()->syncWithoutDetaching($collaborator->getKey());

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
        ]);

    $company->ensureAccountOwnerOnTeam();
    $company->accountTeamMembers()->create([
        'team_id' => $team->getKey(),
        'user_id' => $collaborator->getKey(),
        'role' => AccountTeamRole::CUSTOMER_SUCCESS,
        'access_level' => AccountTeamAccessLevel::EDIT,
    ]);

    $company->refresh();

    expect($company->accountTeam->pluck('id')->all())
        ->toContain($owner->getKey())
        ->toContain($collaborator->getKey());
});
