<?php

declare(strict_types=1);

use App\Events\CrmModelCreated;
use App\Events\CrmModelDeleted;
use App\Events\CrmModelUpdated;
use App\Listeners\Crm\LogCrmModelEvent;
use App\Models\Account;
use Illuminate\Support\Facades\Event;

it('dispatches CRM events for account lifecycle', function (): void {
    Event::fake([
        CrmModelCreated::class,
        CrmModelUpdated::class,
        CrmModelDeleted::class,
    ]);

    $account = Account::factory()->create();

    Event::assertDispatched(fn (\App\Events\CrmModelCreated $event): bool => $event->model->is($account));

    $account->update(['name' => 'Updated '.$account->name]);
    Event::assertDispatched(fn (\App\Events\CrmModelUpdated $event): bool => $event->model->is($account));

    $account->delete();
    Event::assertDispatched(fn (\App\Events\CrmModelDeleted $event): bool => $event->model->is($account));
});

it('registers log listener for CRM model events', function (): void {
    Event::fake();

    Event::assertListening(CrmModelCreated::class, LogCrmModelEvent::class);
    Event::assertListening(CrmModelUpdated::class, LogCrmModelEvent::class);
    Event::assertListening(CrmModelDeleted::class, LogCrmModelEvent::class);
});
