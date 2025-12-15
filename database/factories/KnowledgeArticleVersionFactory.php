<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleVersion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeArticleVersion>
 */
final class KnowledgeArticleVersionFactory extends Factory
{
    protected $model = KnowledgeArticleVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();

        return [
            'article_id' => KnowledgeArticle::factory(),
            'team_id' => Team::factory(),
            'editor_id' => User::factory(),
            'approver_id' => null,
            'version' => $this->faker->numberBetween(1, 5),
            'status' => ArticleStatus::PUBLISHED->value,
            'visibility' => ArticleVisibility::INTERNAL->value,
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => $this->faker->sentence(10),
            'content' => $this->faker->paragraphs(2, true),
            'meta_title' => $title,
            'meta_description' => $this->faker->sentence(15),
            'meta_keywords' => $this->faker->words(4),
            'published_at' => now(),
        ];
    }

    public function configure(): Factory
    {
        return $this->afterMaking(function (KnowledgeArticleVersion $version): void {
            if ($version->article !== null) {
                $version->team_id = $version->article->team_id;
            }
        })->afterCreating(function (KnowledgeArticleVersion $version): void {
            if ($version->article !== null && $version->team_id !== $version->article->team_id) {
                $version->team_id = $version->article->team_id;
                $version->save();
            }
        });
    }
}
