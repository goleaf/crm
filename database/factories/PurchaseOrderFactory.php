<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Team;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PurchaseOrder>
 */
final class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function configure(): static
    {
        return $this->afterMaking(function (PurchaseOrder $purchaseOrder): void {
            $purchaseOrder->registerNumberIfMissing();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderedAt = Carbon::now();

        return [
            'team_id' => Team::factory(),
            'vendor_id' => Vendor::factory(),
            'ordered_at' => $orderedAt,
            'expected_delivery_date' => $orderedAt->copy()->addDays(7),
            'status' => PurchaseOrderStatus::DRAFT,
            'currency_code' => config('company.default_currency', 'USD'),
            'payment_terms' => 'Net 30',
            'shipping_terms' => 'Standard',
            'ship_method' => 'Ground',
            'subtotal' => 0,
            'tax_total' => 0,
            'freight_total' => 0,
            'fee_total' => 0,
            'total' => 0,
            'received_cost' => 0,
            'outstanding_commitment' => 0,
        ];
    }
}
