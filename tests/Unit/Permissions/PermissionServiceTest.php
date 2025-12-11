<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Permissions\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('seeds default roles and permissions for a team', function (): void {
    $team = Team::factory()->create();

    resolve(PermissionService::class)->syncTeamDefinitions($team);

    setPermissionsTeamId($team->getKey());

    expect(Permission::where('name', 'companies.view')->exists())->toBeTrue();
    expect(Role::findByName('editor')->hasPermissionTo('companies.view'))->toBeTrue();
});

it('assigns mapped team roles to users', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    resolve(PermissionService::class)->syncMembership($user, $team, 'admin');

    setPermissionsTeamId($team->getKey());

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->can('companies.view'))->toBeTrue();
});
