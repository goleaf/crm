<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Models\Activity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Generator for creating random Activity instances for property-based testing.
 */
final class ActivityGenerator
{
    /**
     * Generate a random activity event.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generate(
        Team $team,
        Model $subject,
        ?User $causer = null,
        array $overrides = []
    ): Activity {
        $causer ??= User::factory()->create();

        $data = array_merge([
            'team_id' => $team->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'causer_id' => $causer->id,
            'event' => fake()->randomElement(['created', 'updated', 'deleted', 'restored']),
            'changes' => self::generateChanges(),
        ], $overrides);

        return Activity::create($data);
    }

    /**
     * Generate a created event.
     */
    public static function generateCreated(Team $team, Model $subject, ?User $causer = null): Activity
    {
        return self::generate($team, $subject, $causer, [
            'event' => 'created',
            'changes' => ['attributes' => self::generateAttributes()],
        ]);
    }

    /**
     * Generate an updated event.
     */
    public static function generateUpdated(Team $team, Model $subject, ?User $causer = null): Activity
    {
        return self::generate($team, $subject, $causer, [
            'event' => 'updated',
            'changes' => [
                'old' => self::generateAttributes(),
                'attributes' => self::generateAttributes(),
            ],
        ]);
    }

    /**
     * Generate a deleted event.
     */
    public static function generateDeleted(Team $team, Model $subject, ?User $causer = null): Activity
    {
        return self::generate($team, $subject, $causer, [
            'event' => 'deleted',
            'changes' => ['old' => self::generateAttributes()],
        ]);
    }

    /**
     * Generate a restored event.
     */
    public static function generateRestored(Team $team, Model $subject, ?User $causer = null): Activity
    {
        return self::generate($team, $subject, $causer, [
            'event' => 'restored',
            'changes' => ['attributes' => self::generateAttributes()],
        ]);
    }

    /**
     * Generate multiple activities for a subject.
     *
     * @param  int  $count  Number of activities to generate
     * @return array<Activity>
     */
    public static function generateMultiple(
        Team $team,
        Model $subject,
        int $count = 5,
        ?User $causer = null
    ): array {
        $activities = [];

        for ($i = 0; $i < $count; $i++) {
            $activities[] = self::generate($team, $subject, $causer);
        }

        return $activities;
    }

    /**
     * Generate random change data.
     *
     * @return array<string, mixed>
     */
    private static function generateChanges(): array
    {
        $changeTypes = [
            ['attributes' => self::generateAttributes()],
            [
                'old' => self::generateAttributes(),
                'attributes' => self::generateAttributes(),
            ],
        ];

        return fake()->randomElement($changeTypes);
    }

    /**
     * Generate random attribute changes.
     *
     * @return array<string, mixed>
     */
    private static function generateAttributes(): array
    {
        $attributes = [];
        $fieldCount = fake()->numberBetween(1, 5);

        for ($i = 0; $i < $fieldCount; $i++) {
            $field = fake()->randomElement(['title', 'description', 'status', 'priority', 'visibility', 'category']);
            $attributes[$field] = fake()->word();
        }

        return $attributes;
    }

    /**
     * Generate activities with different event types.
     *
     * @return array<string, Activity>
     */
    public static function generateAllEventTypes(Team $team, Model $subject, ?User $causer = null): array
    {
        return [
            'created' => self::generateCreated($team, $subject, $causer),
            'updated' => self::generateUpdated($team, $subject, $causer),
            'deleted' => self::generateDeleted($team, $subject, $causer),
            'restored' => self::generateRestored($team, $subject, $causer),
        ];
    }
}
