<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Knowledge\ApprovalStatus;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleApproval;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeArticleApproval>
 */
final class KnowledgeArticleApprovalFactory extends Factory
{
    protected $model = KnowledgeArticleApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'article_id' => KnowledgeArticle::factory(),
            'requested_by_id' => User::factory(),
            'approver_id' => User::factory(),
            'status' => ApprovalStatus::PENDING->value,
            'due_at' => now()->addDays(2),
            'decision_notes' => null,
        ];
    }

    public function approved(): self
    {
        return $this->state(fn (): array => [
            'status' => ApprovalStatus::APPROVED->value,
            'decided_at' => now(),
        ]);
    }

    public function configure(): Factory
    {
        return $this->afterMaking(function (KnowledgeArticleApproval $approval): void {
            if ($approval->article !== null) {
                $approval->team_id = $approval->article->team_id;
            }
        })->afterCreating(function (KnowledgeArticleApproval $approval): void {
            if ($approval->article !== null && $approval->team_id !== $approval->article->team_id) {
                $approval->forceFill(['team_id' => $approval->article->team_id])->save();
            }
        });
    }
}
