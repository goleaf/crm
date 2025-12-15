<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\InvoicePaymentStatus;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Date;

final class InvoicePaymentObserver
{
    public function creating(InvoicePayment $payment): void
    {
        if ($payment->team_id === null && $payment->invoice !== null) {
            $payment->team_id = $payment->invoice->team_id;
        } elseif ($payment->team_id === null && auth('web')->check()) {
            $payment->team_id = auth('web')->user()?->currentTeam?->getKey();
        }

        $payment->status ??= InvoicePaymentStatus::COMPLETED;
        $payment->paid_at ??= Date::now();
    }

    public function saved(InvoicePayment $payment): void
    {
        $payment->invoice?->syncFinancials();
    }
}
