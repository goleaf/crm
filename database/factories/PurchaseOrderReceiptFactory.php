<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseOrderReceiptType;
use App\Models\PurchaseOrderLineItem;
use App\Models\PurchaseOrderReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PurchaseOrderReceipt>
 */
final class PurchaseOrderReceiptFactory extends Factory
{
    protected $model = PurchaseOrderReceipt::class;

    public function configure(): static
    {
        return $this->afterMaking(function (PurchaseOrderReceipt $receipt): void {
            if ($receipt->purchaseOrder !== null) {
                $receipt->team_id = $receipt->purchaseOrder->team_id;
                $receipt->purchase_order_line_item_id ??= $receipt->purchaseOrder->lineItems()->first()?->id;
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
        $quantity = $this->faker->numberBetween(1, 3);
        $unitCost = $this->faker->randomFloat(2, 25, 300);

        return [
            'purchase_order_id' => null,
            'purchase_order_line_item_id' => PurchaseOrderLineItem::factory(),
            'team_id' => null,
            'received_by_id' => User::factory(),
            'receipt_type' => PurchaseOrderReceiptType::RECEIPT,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => round($quantity * $unitCost, 2),
            'received_at' => Carbon::now(),
            'reference' => $this->faker->bothify('PACK-#####'),
            'notes' => $this->faker->sentence(),
        ];
    }
}
