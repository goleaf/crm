<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
final class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            // Use null defaults - caller should provide these to avoid cascading factory creation
            'team_id' => null,
            'creator_id' => null,
            'category' => NoteCategory::GENERAL->value,
            'visibility' => NoteVisibility::INTERNAL,
            'is_template' => false,
        ];
    }

    /**
     * Create with all related factories (for standalone tests).
     * Use this when you need a fully populated Note without providing relations.
     */
    public function withRelations(): static
    {
        return $this->state(fn (): array => [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
        ]);
    }

    public function configure(): Factory
    {
        // Use minutes instead of seconds to ensure distinct timestamps
        // and avoid flaky sorting tests in fast CI environments
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
