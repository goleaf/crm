<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceReminderType;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceReminder>
 */
final class InvoiceReminderFactory extends Factory
{
    protected $model = InvoiceReminder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'team_id' => Team::factory(),
            'reminder_type' => InvoiceReminderType::DUE_SOON,
            'remind_at' => \Illuminate\Support\Facades\Date::now()->addDays(3),
            'sent_at' => null,
            'channel' => 'email',
            'notes' => $this->faker->sentence(),
        ];
    }
}
