<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryStatusHistory>
 */
final class DeliveryStatusHistoryFactory extends Factory
{
    protected $model = DeliveryStatusHistory::class;

    public function configure(): Factory
    {
        return $this
            ->afterMaking(function (DeliveryStatusHistory $history): void {
                if ($history->delivery !== null) {
                    $history->team_id = $history->delivery->team_id;
                }
            })
            ->afterCreating(function (DeliveryStatusHistory $history): void {
                if ($history->delivery !== null) {
                    $history->team()->associate($history->delivery->team)->save();
                }
            });
    }

    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'team_id' => Team::factory(),
            'from_status' => DeliveryStatus::PENDING,
            'to_status' => DeliveryStatus::IN_TRANSIT,
            'changed_by' => User::factory(),
            'note' => $this->faker->sentence(),
        ];
    }
}
