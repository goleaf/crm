<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeliveryAddressType;
use App\Models\Delivery;
use App\Models\DeliveryAddress;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAddress>
 */
final class DeliveryAddressFactory extends Factory
{
    protected $model = DeliveryAddress::class;

    public function configure(): Factory
    {
        return $this
            ->afterMaking(function (DeliveryAddress $address): void {
                if ($address->delivery !== null) {
                    $address->team_id = $address->delivery->team_id;
                }
            })
            ->afterCreating(function (DeliveryAddress $address): void {
                if ($address->delivery !== null) {
                    $address->team()->associate($address->delivery->team)->save();
                }
            });
    }

    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'team_id' => Team::factory(),
            'type' => DeliveryAddressType::ORIGIN,
            'sequence' => 0,
            'label' => $this->faker->randomElement(['Warehouse', 'Customer', 'Return']),
            'contact_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => (string) $this->faker->numberBetween(10000, 99999),
            'country' => $this->faker->country(),
            'instructions' => $this->faker->sentence(),
        ];
    }
}
