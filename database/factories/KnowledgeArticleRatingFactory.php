<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleRating;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeArticleRating>
 */
final class KnowledgeArticleRatingFactory extends Factory
{
    protected $model = KnowledgeArticleRating::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'article_id' => KnowledgeArticle::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'feedback' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'context' => 'web',
            'ip_address' => $this->faker->ipv6(),
        ];
    }

    public function configure(): Factory
    {
        return $this->afterMaking(function (KnowledgeArticleRating $rating): void {
            if ($rating->article !== null) {
                $rating->team_id = $rating->article->team_id;
            }
        })->afterCreating(function (KnowledgeArticleRating $rating): void {
            if ($rating->article !== null && $rating->team_id !== $rating->article->team_id) {
                $rating->forceFill(['team_id' => $rating->article->team_id])->save();
            }
        });
    }
}
