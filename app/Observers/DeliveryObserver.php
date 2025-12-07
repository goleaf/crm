<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CreationSource;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Team;
use App\Models\User;

final readonly class DeliveryObserver
{
    public function creating(Delivery $delivery): void
    {
        $webGuard = auth('web');

        if ($webGuard->check()) {
            $user = $webGuard->user();
            $team = $user instanceof User ? $user->currentTeam : null;

            if ($user instanceof User && $team instanceof Team) {
                $delivery->creator_id ??= $user->getKey();
                $delivery->team_id ??= $team->getKey();
            }
        }

        $delivery->status ??= DeliveryStatus::PENDING;
        $delivery->creation_source ??= CreationSource::WEB;
        $delivery->registerNumberIfMissing();
    }

    public function updating(Delivery $delivery): void
    {
        if ($delivery->isDirty('status')) {
            $fromStatus = DeliveryStatus::tryFrom((string) $delivery->getOriginal('status'));

            if ($delivery->status === DeliveryStatus::DELIVERED && $delivery->delivered_at === null) {
                $delivery->delivered_at = now();
            }

            if ($delivery->status === DeliveryStatus::CANCELLED && $delivery->cancelled_at === null) {
                $delivery->cancelled_at = now();
            }

            $delivery->recordStatusChange($fromStatus, $delivery->status ?? DeliveryStatus::PENDING, 'Status updated');
        }
    }

    public function created(Delivery $delivery): void
    {
        $delivery->recordStatusChange(null, $delivery->status ?? DeliveryStatus::PENDING, 'Delivery created');
    }
}
