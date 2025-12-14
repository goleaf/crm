<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MergeJobStatus;
use App\Enums\MergeJobType;
use App\Models\Company;
use App\Models\MergeJob;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MergeJob>
 */
final class MergeJobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<MergeJob>
     */
    protected $model = MergeJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(MergeJobType::cases());
        $modelClass = $this->getModelClassForType($type);

        return [
            'team_id' => Team::factory(),
            'type' => $type,
            'primary_model_type' => $modelClass,
            'primary_model_id' => $modelClass::factory(),
            'duplicate_model_type' => $modelClass,
            'duplicate_model_id' => $modelClass::factory(),
            'status' => $this->faker->randomElement(MergeJobStatus::cases()),
            'merge_rules' => [
                'auto_merge_empty_fields' => true,
                'preserve_relationships' => true,
            ],
            'field_selections' => [
                'name' => 'primary',
                'email' => 'duplicate',
                'phone' => 'primary',
            ],
            'transferred_relationships' => [
                'tasks' => $this->faker->numberBetween(0, 10),
                'notes' => $this->faker->numberBetween(0, 5),
            ],
            'merge_preview' => [
                'name' => [
                    'field' => 'name',
                    'primary' => $this->faker->company(),
                    'duplicate' => $this->faker->company(),
                    'recommended' => 'primary',
                ],
            ],
            'created_by' => User::factory(),
            'processed_by' => User::factory(),
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the merge job is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MergeJobStatus::PENDING,
            'processed_by' => null,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the merge job is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MergeJobStatus::COMPLETED,
            'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the merge job has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MergeJobStatus::FAILED,
            'error_message' => $this->faker->sentence(),
            'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Get the model class for a given merge job type.
     */
    private function getModelClassForType(MergeJobType $type): string
    {
        return match ($type) {
            MergeJobType::COMPANY => Company::class,
            MergeJobType::CONTACT => \App\Models\People::class,
            MergeJobType::LEAD => \App\Models\Lead::class,
            MergeJobType::OPPORTUNITY => \App\Models\Opportunity::class,
            MergeJobType::ACCOUNT => \App\Models\Account::class,
        };
    }
}
