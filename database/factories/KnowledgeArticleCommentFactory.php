<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Knowledge\CommentStatus;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeArticleComment>
 */
final class KnowledgeArticleCommentFactory extends Factory
{
    protected $model = KnowledgeArticleComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'article_id' => KnowledgeArticle::factory(),
            'author_id' => User::factory(),
            'body' => $this->faker->sentences(2, true),
            'status' => CommentStatus::APPROVED->value,
            'is_internal' => false,
        ];
    }

    public function configure(): Factory
    {
        return $this->afterMaking(function (KnowledgeArticleComment $comment): void {
            if ($comment->article !== null) {
                $comment->team_id = $comment->article->team_id;
            }
        })->afterCreating(function (KnowledgeArticleComment $comment): void {
            if ($comment->article !== null && $comment->team_id !== $comment->article->team_id) {
                $comment->forceFill(['team_id' => $comment->article->team_id])->save();
            }
        });
    }
}
