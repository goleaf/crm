<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CreationSource;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;

final class OrderObserver
{
    public function creating(Order $order): void
    {
        if ($order->team_id === null && auth('web')->check()) {
            $order->team_id = auth('web')->user()?->currentTeam?->getKey();
        }

        if ($order->creator_id === null && auth('web')->check()) {
            $order->creator_id = auth('web')->id();
        }

        $order->ordered_at ??= Date::now();
        $order->creation_source ??= CreationSource::WEB;
        $order->currency_code ??= config('company.default_currency', 'USD');
        $order->registerNumberIfMissing();
    }

    public function saving(Order $order): void
    {
        $order->registerNumberIfMissing();
    }

    public function saved(Order $order): void
    {
        $order->syncFinancials();

        if ($order->wasChanged(['status', 'fulfillment_status']) && $order->creator !== null) {
            /** @var NotificationService $notifications */
            $notifications = resolve(NotificationService::class);
            $url = Route::has('filament.app.resources.orders.view')
                ? route('filament.app.resources.orders.view', ['record' => $order])
                : null;

            $notifications->sendActivityAlert(
                $order->creator,
                title: 'Order updated',
                message: sprintf(
                    'Order %s is %s (%s).',
                    $order->number ?? $order->id,
                    $order->status->label(),
                    $order->fulfillment_status->label()
                ),
                url: $url
            );
        }
    }
}
