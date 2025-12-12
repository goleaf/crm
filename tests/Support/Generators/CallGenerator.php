<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Models\Call;
use App\Models\Team;
use App\Models\User;

/**
 * Generator for creating random Call instances for property-based testing.
 */
final class CallGenerator
{
    /**
     * Generate a random call with all fields populated.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generate(Team $team, ?User $creator = null, array $overrides = []): Call
    {
        $creator ??= User::factory()->create();

        $scheduledAt = fake()->optional(0.6)->dateTimeBetween('-1 week', '+1 week');
        $startedAt = fake()->optional(0.8)->dateTimeBetween('-1 week', 'now');
        $endedAt = $startedAt ? fake()->dateTimeBetween($startedAt, $startedAt->format('Y-m-d H:i:s') . ' +2 hours') : null;

        $data = array_merge([
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'phone_number' => fake()->phoneNumber(),
            'contact_name' => fake()->optional(0.8)->name(),
            'purpose' => fake()->optional(0.7)->sentence(4),
            'outcome' => fake()->optional(0.6)->sentence(6),
            'duration_minutes' => $endedAt && $startedAt ? fake()->numberBetween(1, 120) : null,
            'scheduled_at' => $scheduledAt,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'status' => fake()->randomElement(['scheduled', 'in_progress', 'completed', 'missed', 'canceled']),
            'participants' => self::generateParticipants(),
            'notes' => fake()->optional(0.7)->paragraph(),
            'follow_up_required' => fake()->boolean(30),
            'voip_call_id' => fake()->optional(0.5)->uuid(),
            'recording_url' => fake()->optional(0.2)->url(),
        ], $overrides);

        return Call::factory()->create($data);
    }

    /**
     * Generate a scheduled call.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateScheduled(Team $team, ?User $creator = null, array $overrides = []): Call
    {
        return self::generate($team, $creator, array_merge([
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 week'),
            'started_at' => null,
            'ended_at' => null,
        ], $overrides));
    }

    /**
     * Generate a completed call.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateCompleted(Team $team, ?User $creator = null, array $overrides = []): Call
    {
        $startedAt = fake()->dateTimeBetween('-1 week', '-1 hour');
        $endedAt = fake()->dateTimeBetween($startedAt, $startedAt->format('Y-m-d H:i:s') . ' +2 hours');

        return self::generate($team, $creator, array_merge([
            'status' => 'completed',
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => fake()->numberBetween(1, 120),
            'outcome' => fake()->sentence(6),
        ], $overrides));
    }

    /**
     * Generate a call linked to a record.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateWithRelated(Team $team, object $relatedRecord, ?User $creator = null, array $overrides = []): Call
    {
        return self::generate($team, $creator, array_merge([
            'related_id' => $relatedRecord->id,
            'related_type' => $relatedRecord::class,
        ], $overrides));
    }

    /**
     * Generate participants array.
     *
     * @return array<int, array{name: string, phone?: string, role?: string}>
     */
    private static function generateParticipants(): array
    {
        $count = fake()->numberBetween(1, 5);
        $participants = [];

        for ($i = 0; $i < $count; $i++) {
            $participants[] = [
                'name' => fake()->name(),
                'phone' => fake()->optional(0.8)->phoneNumber(),
                'role' => fake()->optional(0.6)->randomElement(['caller', 'recipient', 'participant']),
            ];
        }

        return $participants;
    }

    /**
     * Generate random call data without creating a model.
     *
     * @return array<string, mixed>
     */
    public static function generateData(Team $team, ?User $creator = null): array
    {
        $creator ??= User::factory()->create();

        return [
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'phone_number' => fake()->phoneNumber(),
            'contact_name' => fake()->optional(0.8)->name(),
            'status' => fake()->randomElement(['scheduled', 'completed', 'missed']),
            'participants' => self::generateParticipants(),
        ];
    }
}
