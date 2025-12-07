<?php

declare(strict_types=1);

namespace App\Listeners\Crm;

use App\Events\CrmModelEvent;
use Illuminate\Support\Facades\Log;

final class LogCrmModelEvent
{
    public function handle(CrmModelEvent $event): void
    {
        $model = $event->model;

        Log::info('crm-model-event', [
            'action' => $event->action,
            'model' => $event->modelType(),
            'id' => $model->getKey(),
            'team_id' => $model->team_id ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
