<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeArticle>
 */
final class KnowledgeArticleFactory extends Factory
{
    protected $model = KnowledgeArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'author_id' => User::factory(),
            'category_id' => KnowledgeCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => $this->faker->sentence(12),
            'content' => $this->faker->paragraphs(3, true),
            'status' => ArticleStatus::DRAFT->value,
            'visibility' => ArticleVisibility::INTERNAL->value,
            'meta_title' => $title,
            'meta_description' => $this->faker->sentence(20),
            'meta_keywords' => $this->faker->words(5),
            'allow_comments' => true,
            'allow_ratings' => true,
            'is_featured' => false,
            'view_count' => 0,
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ];
    }

    public function configure(): Factory
    {
        return $this
            ->afterMaking(function (KnowledgeArticle $article): void {
                if ($article->category !== null && $article->team_id !== null) {
                    $article->category->team_id = $article->team_id;
                }
            })
            ->afterCreating(function (KnowledgeArticle $article): void {
                if ($article->category !== null && $article->category->team_id !== $article->team_id) {
                    $article->category->forceFill(['team_id' => $article->team_id])->save();
                }
            });
    }
}
