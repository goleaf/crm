<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Route;

final readonly class InvoiceObserver
{
    public function creating(Invoice $invoice): void
    {
        $invoice->registerNumberIfMissing();
    }

    public function updating(Invoice $invoice): void
    {
        if ($invoice->isDirty('status')) {
            $fromStatus = InvoiceStatus::tryFrom((string) $invoice->getOriginal('status'));
            $invoice->recordStatusChange($fromStatus, $invoice->status, 'Status updated');

            if ($invoice->creator !== null) {
                /** @var NotificationService $notifications */
                $notifications = app(NotificationService::class);
                $url = Route::has('filament.app.resources.invoices.view')
                    ? route('filament.app.resources.invoices.view', ['record' => $invoice])
                    : null;

                $notifications->sendActivityAlert(
                    $invoice->creator,
                    title: 'Invoice status updated',
                    message: sprintf(
                        'Invoice %s moved to %s.',
                        $invoice->number ?? $invoice->id,
                        $invoice->status->label()
                    ),
                    url: $url
                );
            }
        }
    }

    public function created(Invoice $invoice): void
    {
        $invoice->recordStatusChange(null, $invoice->status ?? InvoiceStatus::DRAFT, 'Invoice created');
        $invoice->syncFinancials();
    }

    public function saved(Invoice $invoice): void
    {
        $invoice->syncFinancials();
    }
}
