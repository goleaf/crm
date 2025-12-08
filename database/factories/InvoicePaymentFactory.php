<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoicePaymentStatus;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicePayment>
 */
final class InvoicePaymentFactory extends Factory
{
    protected $model = InvoicePayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'team_id' => Team::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 500),
            'currency_code' => config('company.default_currency', 'USD'),
            'paid_at' => \Illuminate\Support\Facades\Date::now(),
            'method' => $this->faker->randomElement(['card', 'bank_transfer', 'cash']),
            'reference' => $this->faker->uuid(),
            'status' => InvoicePaymentStatus::COMPLETED,
            'notes' => null,
        ];
    }
}
