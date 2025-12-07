<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryAddress;
use App\Models\DeliveryStatusHistory;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::all();
        $users = User::all();
        $orders = Order::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $this->command->info('Creating sample deliveries with addresses and status history...');

        foreach ($teams as $team) {
            Delivery::factory()
                ->count(10)
                ->for($team)
                ->state([
                    'creator_id' => $users->random()->id,
                    'order_id' => $orders->isNotEmpty() ? $orders->random()->id : null,
                    'status' => fake()->randomElement(DeliveryStatus::cases()),
                ])
                ->has(DeliveryAddress::factory()->count(2), 'addresses')
                ->has(
                    DeliveryStatusHistory::factory()
                        ->count(2)
                        ->state(fn (): array => [
                            'from_status' => DeliveryStatus::PENDING,
                            'to_status' => fake()->randomElement([DeliveryStatus::SCHEDULED, DeliveryStatus::IN_TRANSIT, DeliveryStatus::DELIVERED]),
                        ]),
                    'statusUpdates'
                )
                ->create();
        }

        $this->command->info('✓ Created deliveries with addresses and status history');
    }
}
