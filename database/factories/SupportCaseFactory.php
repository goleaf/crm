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
 * Factory for creating SupportCase model instances.
 *
 * This factory uses null defaults for all relationship fields to prevent cascading
 * factory creation. Use the fluent state methods to configure relationships:
 *
 * - `withRelations()` - Creates all related models (Team, User, Company, People)
 * - `forTeam($team)` - Scopes case to a specific team
 * - `assignedToSameTeam()` - Sets assigned_team_id to match team_id
 *
 * Status state methods:
 * - `open()` - Sets status to NEW
 * - `closed()` - Sets status to CLOSED with resolved_at timestamp
 * - `pendingInput()` - Sets status to PENDING_INPUT
 * - `assigned()` - Sets status to ASSIGNED
 * - `highPriority()` - Sets priority to P1
 * - `overdue()` - Sets SLA as breached (past due)
 *
 * @example Basic usage with team
 * ```php
 * $team = Team::factory()->create();
 * $case = SupportCase::factory()
 *     ->forTeam($team)
 *     ->open()
 *     ->create();
 * ```
 *
 * @example Standalone test with all relations
 * ```php
 * $case = SupportCase::factory()
 *     ->withRelations()
 *     ->highPriority()
 *     ->create();
 * ```
 *
 * @extends Factory<SupportCase>
 *
 * @see SupportCase
 * @see \Tests\Unit\Factories\SupportCaseFactoryTest
 */
final class SupportCaseFactory extends Factory
{
    protected $model = SupportCase::class;

    /**
     * Define the model's default state.
     *
     * All relationship fields default to null to prevent cascading factory creation.
     * Use `withRelations()` or provide explicit values when relationships are needed.
     *
     * @return array<string, mixed>
     */
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
            // Use null defaults - caller should provide these to avoid cascading factory creation
            'team_id' => null,
            'creator_id' => null,
            'company_id' => null,
            'contact_id' => null,
            'assigned_to_id' => null,
            'assigned_team_id' => null,
        ];
    }

    /**
     * Create with all related factories (for standalone tests).
     *
     * Use this when you need a fully populated SupportCase without providing relations.
     * Creates Team, User (creator), Company, People (contact), and User (assignee).
     * The assigned_team_id is automatically set to match team_id.
     *
     * @return static
     *
     * @example
     * ```php
     * $case = SupportCase::factory()->withRelations()->create();
     * // $case->team, $case->creator, $case->company, $case->contact, $case->assignee are all populated
     * ```
     */
    public function withRelations(): static
    {
        return $this->afterMaking(function (SupportCase $case): void {
            if ($case->team_id === null) {
                $team = Team::factory()->create();
                $case->team_id = $team->id;
                $case->assigned_team_id = $team->id;
            }
            if ($case->creator_id === null) {
                $case->creator_id = User::factory()->create()->id;
            }
            if ($case->company_id === null) {
                $case->company_id = Company::factory()->create()->id;
            }
            if ($case->contact_id === null) {
                $case->contact_id = People::factory()->create()->id;
            }
            if ($case->assigned_to_id === null) {
                $case->assigned_to_id = User::factory()->create()->id;
            }
        });
    }

    /**
     * Create with a specific team and related entities scoped to that team.
     *
     * Sets both team_id and assigned_team_id to the provided team.
     * If no team is provided, creates a new one.
     *
     * @param  Team|null  $team  The team to use, or null to create a new one
     * @return static
     *
     * @example
     * ```php
     * $team = Team::factory()->create();
     * $case = SupportCase::factory()->forTeam($team)->create();
     * // $case->team_id === $team->id && $case->assigned_team_id === $team->id
     * ```
     */
    public function forTeam(?Team $team = null): static
    {
        $team ??= Team::factory()->create();

        return $this->state(fn (): array => [
            'team_id' => $team->id,
            'assigned_team_id' => $team->id,
        ]);
    }

    /**
     * Assign to the same team as the case's team_id.
     *
     * Ensures assigned_team_id matches team_id for consistent team scoping.
     * Useful when team_id is set via state but assigned_team_id needs to match.
     *
     * @return static
     *
     * @example
     * ```php
     * $team = Team::factory()->create();
     * $case = SupportCase::factory()
     *     ->state(['team_id' => $team->id])
     *     ->assignedToSameTeam()
     *     ->create();
     * ```
     */
    public function assignedToSameTeam(): static
    {
        return $this->afterMaking(function (SupportCase $case): void {
            $case->assigned_team_id = $case->team_id;
        })->afterCreating(function (SupportCase $case): void {
            if ($case->assigned_team_id !== $case->team_id) {
                $case->update(['assigned_team_id' => $case->team_id]);
            }
        });
    }

    /**
     * Set the case status to open/new.
     *
     * @return static
     */
    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => CaseStatus::NEW,
            'resolved_at' => null,
        ]);
    }

    /**
     * Set the case status to closed (resolved).
     *
     * Sets resolved_at to current timestamp.
     *
     * @return static
     */
    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => CaseStatus::CLOSED,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Set the case status to pending input.
     *
     * @return static
     */
    public function pendingInput(): static
    {
        return $this->state(fn (): array => [
            'status' => CaseStatus::PENDING_INPUT,
            'resolved_at' => null,
        ]);
    }

    /**
     * Set the case status to assigned.
     *
     * @return static
     */
    public function assigned(): static
    {
        return $this->state(fn (): array => [
            'status' => CaseStatus::ASSIGNED,
            'resolved_at' => null,
        ]);
    }

    /**
     * Set the case as high priority (P1).
     *
     * @return static
     */
    public function highPriority(): static
    {
        return $this->state(fn (): array => [
            'priority' => CasePriority::P1,
        ]);
    }

    /**
     * Set the case as overdue (SLA breached).
     *
     * Sets sla_due_at to yesterday and sla_breached to true.
     *
     * @return static
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'sla_due_at' => now()->subDay(),
            'sla_breached' => true,
            'resolved_at' => null,
        ]);
    }

    /**
     * Configure the factory with sequence-based timestamps.
     *
     * Each record in a sequence gets progressively older timestamps,
     * useful for testing chronological ordering.
     *
     * @return Factory<SupportCase>
     */
    public function configure(): Factory
    {
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
