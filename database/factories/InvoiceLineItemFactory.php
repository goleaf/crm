<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLineItem>
 */
final class InvoiceLineItemFactory extends Factory
{
    protected $model = InvoiceLineItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'team_id' => Team::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => $this->faker->randomFloat(2, 50, 500),
            'tax_rate' => $this->faker->randomElement([0, 5, 8, 10]),
            'sort_order' => 1,
        ];
    }
}
