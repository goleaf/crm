<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CreationSource;
use App\Models\Opportunity;
use App\Models\Team;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Opportunity>
 */
final class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $teamId = CurrentTeamResolver::resolveId();

        return [
            'name' => $this->faker->sentence(),
            'team_id' => $teamId ?? Team::factory(),
            'creation_source' => CreationSource::WEB->value,
        ];
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
