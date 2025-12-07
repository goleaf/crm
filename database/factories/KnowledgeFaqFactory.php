<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Knowledge\ArticleVisibility;
use App\Enums\Knowledge\FaqStatus;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFaq;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeFaq>
 */
final class KnowledgeFaqFactory extends Factory
{
    protected $model = KnowledgeFaq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'article_id' => KnowledgeArticle::factory(),
            'creator_id' => User::factory(),
            'question' => $this->faker->sentence(8),
            'answer' => $this->faker->paragraph(),
            'status' => FaqStatus::PUBLISHED->value,
            'visibility' => ArticleVisibility::PUBLIC->value,
            'position' => $this->faker->numberBetween(1, 20),
        ];
    }

    public function configure(): Factory
    {
        return $this->afterMaking(function (KnowledgeFaq $faq): void {
            if ($faq->article !== null) {
                $faq->team_id = $faq->article->team_id;
            }
        })->afterCreating(function (KnowledgeFaq $faq): void {
            if ($faq->article !== null && $faq->team_id !== $faq->article->team_id) {
                $faq->forceFill(['team_id' => $faq->article->team_id])->save();
            }
        });
    }
}
