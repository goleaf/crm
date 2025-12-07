<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeTag;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeTag>
 */
final class KnowledgeTagFactory extends Factory
{
    protected $model = KnowledgeTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => Str::headline($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
        ];
    }
}
