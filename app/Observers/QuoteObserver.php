<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\QuoteStatus;
use App\Models\Quote;

final readonly class QuoteObserver
{
    public function updating(Quote $quote): void
    {
        if ($quote->isDirty('status')) {
            $fromStatus = QuoteStatus::tryFrom((string) $quote->getOriginal('status'));
            $quote->recordStatusChange($fromStatus, $quote->status, 'Status updated');
        }
    }

    public function created(Quote $quote): void
    {
        $quote->recordStatusChange(null, $quote->status ?? QuoteStatus::DRAFT, 'Quote created');
        $quote->syncFinancials();
    }

    public function saved(Quote $quote): void
    {
        $quote->syncFinancials();
    }
}
