<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CrmModelCreated;
use App\Events\CrmModelDeleted;
use App\Events\CrmModelUpdated;
use App\Listeners\Auth\BackfillTimezoneFromGeoGenius;
use App\Listeners\Crm\CrmEventSubscriber;
use App\Listeners\Crm\LogCrmModelEvent;
use App\Listeners\Permissions\RemoveTeamRole;
use App\Listeners\Permissions\SeedTeamPermissions;
use App\Listeners\Permissions\SyncTeamMemberRole;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Laravel\Jetstream\Events\TeamMemberRemoved;
use Laravel\Jetstream\Events\TeamMemberUpdated;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TeamCreated::class => [
            SeedTeamPermissions::class,
        ],
        TeamMemberAdded::class => [
            SyncTeamMemberRole::class,
        ],
        TeamMemberUpdated::class => [
            SyncTeamMemberRole::class,
        ],
        TeamMemberRemoved::class => [
            RemoveTeamRole::class,
        ],
        CrmModelCreated::class => [
            LogCrmModelEvent::class,
        ],
        CrmModelUpdated::class => [
            LogCrmModelEvent::class,
        ],
        CrmModelDeleted::class => [
            LogCrmModelEvent::class,
        ],
        Login::class => [
            BackfillTimezoneFromGeoGenius::class,
        ],
    ];

    /**
     * @var list<class-string>
     */
    protected $subscribe = [
        CrmEventSubscriber::class,
    ];
}
