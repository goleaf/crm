<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Knowledge\ArticleVisibility;
use App\Models\KnowledgeCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeCategory>
 */
final class KnowledgeCategoryFactory extends Factory
{
    protected $model = KnowledgeCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(asText: true);

        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => Str::headline($name),
            'slug' => Str::slug($name),
            'visibility' => ArticleVisibility::INTERNAL->value,
            'description' => $this->faker->sentence(),
            'position' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function forTeam(Team $team): self
    {
        return $this->state(fn (): array => ['team_id' => $team->getKey()]);
    }
}
