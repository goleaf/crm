<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Order $order): void {
            $order->registerNumberIfMissing();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderedAt = \Illuminate\Support\Facades\Date::now();

        return [
            'team_id' => Team::factory(),
            'company_id' => Company::factory(),
            'contact_id' => People::factory(),
            'ordered_at' => $orderedAt,
            'fulfillment_due_at' => $orderedAt->copy()->addDays(14),
            'payment_terms' => 'Net 30',
            'currency_code' => config('company.default_currency', 'USD'),
            'status' => OrderStatus::DRAFT,
            'fulfillment_status' => OrderFulfillmentStatus::PENDING,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'balance_due' => 0,
            'paid_total' => 0,
            'invoiced_total' => 0,
        ];
    }
}
