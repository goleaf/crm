<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Models\Company;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportCase>
 */
final class SupportCaseFactory extends Factory
{
    protected $model = SupportCase::class;

    public function definition(): array
    {
        return [
            'case_number' => 'CASE-'.Str::upper(Str::random(8)),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(CaseStatus::cases()),
            'priority' => $this->faker->randomElement(CasePriority::cases()),
            'type' => $this->faker->randomElement(CaseType::cases()),
            'channel' => $this->faker->randomElement(CaseChannel::cases()),
            'queue' => $this->faker->randomElement(['general', 'billing', 'technical', 'product']),
            'sla_due_at' => $this->faker->dateTimeBetween('+1 day', '+5 days'),
            'first_response_at' => null,
            'resolved_at' => null,
            'thread_reference' => $this->faker->optional()->uuid(),
            'customer_portal_url' => $this->faker->optional()->url(),
            'knowledge_base_reference' => $this->faker->optional()->slug(),
            'email_message_id' => $this->faker->optional()->uuid(),
            'created_at' => now(),
            'updated_at' => now(),
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'company_id' => Company::factory(),
            'contact_id' => People::factory(),
            'assigned_to_id' => User::factory(),
            'assigned_team_id' => Team::factory(),
        ];
    }

    public function configure(): Factory
    {
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
