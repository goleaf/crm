<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderNumberGenerator
{
    /**
     * @return array{number: string, sequence: int}
     */
    public function generate(int $teamId, ?Carbon $orderedAt = null): array
    {
        $orderedAt ??= Carbon::now();
        $year = $orderedAt->format('Y');

        $sequence = DB::transaction(function () use ($teamId, $year): int {
            $latest = PurchaseOrder::query()
                ->where('team_id', $teamId)
                ->whereYear('ordered_at', $year)
                ->lockForUpdate()
                ->max('sequence');

            return ((int) $latest) + 1;
        }, attempts: 1);

        return [
            'sequence' => $sequence,
            'number' => sprintf('PO-%s-%05d', $year, $sequence),
        ];
    }
}
