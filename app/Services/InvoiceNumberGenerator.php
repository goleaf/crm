<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class InvoiceNumberGenerator
{
    /**
     * @return array{number: string, sequence: int}
     */
    public function generate(int $teamId, ?Carbon $issueDate = null): array
    {
        $issueDate ??= Carbon::now();
        $year = $issueDate->format('Y');

        $sequence = DB::transaction(function () use ($teamId, $year): int {
            $latest = Invoice::query()
                ->where('team_id', $teamId)
                ->whereYear('issue_date', $year)
                ->lockForUpdate()
                ->max('sequence');

            return ((int) $latest) + 1;
        }, attempts: 1);

        return [
            'sequence' => $sequence,
            'number' => sprintf('INV-%s-%05d', $year, $sequence),
        ];
    }
}
