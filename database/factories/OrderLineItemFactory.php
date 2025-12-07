<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderLineItem>
 */
final class OrderLineItemFactory extends Factory
{
    protected $model = OrderLineItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'team_id' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'quantity' => 1,
            'fulfilled_quantity' => 0,
            'unit_price' => 100,
            'tax_rate' => 0,
        ];
    }
}
