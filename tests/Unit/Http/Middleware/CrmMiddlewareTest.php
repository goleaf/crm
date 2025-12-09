<?php

declare(strict_types=1);

use App\Http\Middleware\ApplyCustomMiddleware;
use App\Http\Middleware\EnsureCrmAuthenticated;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureTeamContext;
use App\Models\Team;
use App\Models\User;
use App\Permissions\TeamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

it('redirects guests through CRM auth middleware', function (): void {
    $middleware = new EnsureCrmAuthenticated;

    $response = $middleware->handle(Request::create('/crm', 'GET'), fn (): \Symfony\Component\HttpFoundation\Response => response('ok'));

    expect($response->isRedirection())->toBeTrue();
});

it('requires a team context for authenticated requests', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $middleware = new EnsureCrmAuthenticated;
    $request = Request::create('/crm', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);

    $response = $middleware->handle($request, fn (): \Symfony\Component\HttpFoundation\Response => response('ok'));

    expect($response->getStatusCode())->toBe(403);
});

it('sets permission team id via team context middleware', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $team->users()->attach($user, ['role' => 'admin']);
    $user->switchTeam($team);
    Auth::login($user);

    resolve(TeamResolver::class)->setPermissionsTeamId(null);

    $middleware = new EnsureTeamContext;
    $middleware->handle(Request::create('/crm', 'GET'), fn (): \Symfony\Component\HttpFoundation\Response => response('ok'));

    expect(resolve(TeamResolver::class)->getPermissionsTeamId())->toBe($team->getKey());
});

it('denies missing permissions and allows granted permissions', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $team->users()->attach($user, ['role' => 'admin']);
    $user->switchTeam($team);
    Auth::login($user);

    $permissionRegistrar = resolve(PermissionRegistrar::class);
    $permissionRegistrar->setPermissionsTeamId($team->getKey());
    $permissionRegistrar->forgetCachedPermissions();

    $permission = Permission::create([
        'name' => 'orders.view',
        'guard_name' => 'web',
        'team_id' => $team->getKey(),
    ]);

    $permissionRegistrar->setPermissionsTeamId($team->getKey());
    $user->givePermissionTo($permission);

    $request = Request::create('/crm', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);

    $denyResponse = (new EnsurePermission)->handle(
        $request,
        fn (): \Symfony\Component\HttpFoundation\Response => response('ok'),
        'orders.update',
    );
    expect($denyResponse->getStatusCode())->toBe(403);

    $allowResponse = (new EnsurePermission)->handle(
        $request,
        fn (): \Symfony\Component\HttpFoundation\Response => response('ok'),
        'orders.view',
    );
    expect($allowResponse->getContent())->toBe('ok');
});

it('runs custom middleware callbacks', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $team->users()->attach($user, ['role' => 'admin']);
    $user->switchTeam($team);
    Auth::login($user);

    $ran = false;
    config()->set('crm.custom_middleware', [
        function (Request $request, Closure $next) use (&$ran) {
            $ran = true;

            return $next($request);
        },
    ]);

    (new ApplyCustomMiddleware)->handle(
        Request::create('/crm', 'GET'),
        fn (): \Symfony\Component\HttpFoundation\Response => response('ok'),
    );

    expect($ran)->toBeTrue();
});
