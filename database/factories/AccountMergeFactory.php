<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccountMerge;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountMerge>
 */
final class AccountMergeFactory extends Factory
{
    protected $model = AccountMerge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'primary_company_id' => Company::factory(),
            'duplicate_company_id' => Company::factory(),
            'merged_by_user_id' => User::factory(),
            'field_selections' => [
                'name' => 'primary',
                'website' => 'primary',
                'industry' => 'duplicate',
                'revenue' => 'primary',
                'employee_count' => 'duplicate',
                'description' => 'primary',
            ],
            'transferred_relationships' => [
                'people' => $this->faker->numberBetween(0, 10),
                'opportunities' => $this->faker->numberBetween(0, 5),
                'tasks' => $this->faker->numberBetween(0, 8),
                'notes' => $this->faker->numberBetween(0, 15),
            ],
        ];
    }
}
