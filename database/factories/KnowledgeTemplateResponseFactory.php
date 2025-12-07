<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Knowledge\ArticleVisibility;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTemplateResponse;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeTemplateResponse>
 */
final class KnowledgeTemplateResponseFactory extends Factory
{
    protected $model = KnowledgeTemplateResponse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'category_id' => KnowledgeCategory::factory(),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(3),
            'visibility' => ArticleVisibility::INTERNAL->value,
            'is_active' => true,
        ];
    }

    public function configure(): Factory
    {
        return $this->afterMaking(function (KnowledgeTemplateResponse $template): void {
            if ($template->category !== null && $template->team_id !== null) {
                $template->category->team_id = $template->team_id;
            }
        })->afterCreating(function (KnowledgeTemplateResponse $template): void {
            if ($template->category !== null && $template->category->team_id !== $template->team_id) {
                $template->category->forceFill(['team_id' => $template->team_id])->save();
            }
        });
    }
}
