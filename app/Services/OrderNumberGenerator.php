<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;

final class OrderNumberGenerator
{
    /**
     * @return array{number: string, sequence: int}
     */
    public function generate(int $teamId, ?Carbon $orderedAt = null): array
    {
        $orderedAt ??= \Illuminate\Support\Facades\Date::now();
        $order = new Order([
            'team_id' => $teamId,
            'ordered_at' => $orderedAt,
        ]);
        $order->registerNumberIfMissing();

        return [
            'sequence' => (int) $order->sequence,
            'number' => (string) $order->number,
        ];
    }
}
