<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceStatusHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceStatusHistory>
 */
final class InvoiceStatusHistoryFactory extends Factory
{
    protected $model = InvoiceStatusHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'team_id' => Team::factory(),
            'from_status' => InvoiceStatus::DRAFT,
            'to_status' => InvoiceStatus::SENT,
            'changed_by' => User::factory(),
            'note' => $this->faker->sentence(),
        ];
    }
}
