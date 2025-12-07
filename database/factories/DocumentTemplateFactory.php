<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentTemplate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplate>
 */
final class DocumentTemplateFactory extends Factory
{
    protected $model = DocumentTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'is_default' => false,
        ];
    }
}
