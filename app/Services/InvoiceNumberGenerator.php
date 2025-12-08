<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Carbon;

final class InvoiceNumberGenerator
{
    /**
     * @return array{number: string, sequence: int}
     */
    public function generate(int $teamId, ?Carbon $issueDate = null): array
    {
        $issueDate ??= \Illuminate\Support\Facades\Date::now();
        $invoice = new Invoice([
            'team_id' => $teamId,
            'issue_date' => $issueDate,
        ]);
        $invoice->registerNumberIfMissing();

        return [
            'sequence' => (int) $invoice->sequence,
            'number' => (string) $invoice->number,
        ];
    }
}
