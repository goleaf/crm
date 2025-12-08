<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Invoice $invoice): void {
            $invoice->registerNumberIfMissing();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = \Illuminate\Support\Facades\Date::now();

        return [
            'team_id' => Team::factory(),
            'company_id' => Company::factory(),
            'contact_id' => People::factory(),
            'issue_date' => $issueDate,
            'due_date' => $issueDate->copy()->addDays(30),
            'payment_terms' => 'Net 30',
            'currency_code' => config('company.default_currency', 'USD'),
            'status' => InvoiceStatus::DRAFT,
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'late_fee_rate' => 0,
            'total' => 0,
            'balance_due' => 0,
        ];
    }
}
