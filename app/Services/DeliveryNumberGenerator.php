<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Delivery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DeliveryNumberGenerator
{
    /**
     * @return array{number: string, sequence: int}
     */
    public function generate(int $teamId, ?Carbon $deliveryDate = null): array
    {
        $deliveryDate ??= \Illuminate\Support\Facades\Date::now();

        $sequence = DB::transaction(function () use ($teamId): int {
            $latest = Delivery::query()
                ->where('team_id', $teamId)
                ->lockForUpdate()
                ->max('sequence');

            return ((int) $latest) + 1;
        }, attempts: 1);

        return [
            'sequence' => $sequence,
            'number' => sprintf('DLV-%s-%05d', $deliveryDate->format('Y'), $sequence),
        ];
    }
}
