<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
final class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function configure(): Factory
    {
        return $this->afterMaking(function (Delivery $delivery): void {
            $delivery->registerNumberIfMissing();
        });
    }

    public function definition(): array
    {
        $pickupAt = \Illuminate\Support\Facades\Date::now()->addDays($this->faker->numberBetween(0, 3));

        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'order_id' => null,
            'status' => DeliveryStatus::PENDING,
            'carrier' => $this->faker->randomElement(['UPS', 'FedEx', 'DHL', 'USPS', 'Local Courier']),
            'tracking_number' => strtoupper($this->faker->bothify('TRK#####??')),
            'order_reference' => $this->faker->bothify('ORD-#####'),
            'scheduled_pickup_at' => $pickupAt,
            'scheduled_delivery_at' => $pickupAt->copy()->addDays(2),
            'instructions' => $this->faker->sentence(),
        ];
    }

    public function delivered(): Factory
    {
        return $this->state(fn (): array => [
            'status' => DeliveryStatus::DELIVERED,
            'delivered_at' => \Illuminate\Support\Facades\Date::now(),
        ]);
    }

    public function withOrder(): Factory
    {
        return $this->afterCreating(function (Delivery $delivery): void {
            $order = Order::query()->create([
                'team_id' => $delivery->team_id,
                'creator_id' => $delivery->creator_id,
                'status' => OrderStatus::PENDING,
                'currency_code' => config('company.default_currency', 'USD'),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'expected_delivery_date' => $delivery->scheduled_delivery_at?->toDateString(),
                'line_items' => [],
            ]);

            $delivery->order()->associate($order);
            $delivery->save();
        });
    }
}
