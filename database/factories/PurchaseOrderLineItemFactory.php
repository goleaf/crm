<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderLineItem>
 */
final class PurchaseOrderLineItemFactory extends Factory
{
    protected $model = PurchaseOrderLineItem::class;

    public function configure(): static
    {
        return $this->afterMaking(function (PurchaseOrderLineItem $lineItem): void {
            if ($lineItem->purchaseOrder !== null) {
                $lineItem->team_id = $lineItem->purchaseOrder->team_id;
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitCost = $this->faker->randomFloat(2, 50, 500);
        $lineTotal = round($quantity * $unitCost, 2);
        $taxRate = 5.0;
        $taxTotal = round($lineTotal * ($taxRate / 100), 2);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'team_id' => null,
            'order_line_item_id' => null,
            'name' => $this->faker->word().' component',
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
            'tax_total' => $taxTotal,
            'expected_receipt_at' => now()->addDays(5),
        ];
    }
}
