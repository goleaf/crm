<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;

final class PurchaseOrderNumberGenerator
{
    /**
     * @return array{number: string, sequence: int}
     */
    public function generate(int $teamId, ?Carbon $orderedAt = null): array
    {
        $orderedAt ??= \Illuminate\Support\Facades\Date::now();
        $purchaseOrder = new PurchaseOrder([
            'team_id' => $teamId,
            'ordered_at' => $orderedAt,
        ]);
        $purchaseOrder->registerNumberIfMissing();

        return [
            'sequence' => (int) $purchaseOrder->sequence,
            'number' => (string) $purchaseOrder->number,
        ];
    }
}
