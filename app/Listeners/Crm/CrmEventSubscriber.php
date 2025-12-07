<?php

declare(strict_types=1);

namespace App\Listeners\Crm;

use App\Events\CrmModelCreated;
use App\Events\CrmModelDeleted;
use App\Events\CrmModelUpdated;
use App\Models\Account;
use App\Models\Company;
use App\Models\People;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;

final class CrmEventSubscriber
{
    /**
     * @var list<class-string<Model>>
     */
    private array $models = [
        Account::class,
        Company::class,
        People::class,
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->models as $model) {
            $events->listen("eloquent.created: {$model}", function (Model $instance): void {
                event(new CrmModelCreated($instance));
            });
            $events->listen("eloquent.updated: {$model}", function (Model $instance): void {
                event(new CrmModelUpdated($instance));
            });
            $events->listen("eloquent.deleted: {$model}", function (Model $instance): void {
                event(new CrmModelDeleted($instance));
            });
        }
    }
}
