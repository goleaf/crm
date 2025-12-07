<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\InvoiceReminder;

final class InvoiceReminderObserver
{
    public function creating(InvoiceReminder $reminder): void
    {
        if ($reminder->team_id === null && $reminder->invoice !== null) {
            $reminder->team_id = $reminder->invoice->team_id;
        } elseif ($reminder->team_id === null && auth('web')->check()) {
            $reminder->team_id = auth('web')->user()?->currentTeam?->getKey();
        }
    }
}
