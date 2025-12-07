<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\CompanyRevenue;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyRevenue>
 */
final class CompanyRevenueFactory extends Factory
{
    protected $model = CompanyRevenue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = array_keys(config('company.currency_codes', ['USD' => 'USD']));

        return [
            'company_id' => Company::factory(),
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'year' => (int) $this->faker->numberBetween((int) now()->subYears(10)->year, (int) now()->addYear()->year),
            'amount' => $this->faker->randomFloat(2, 10_000, 100_000_000),
            'currency_code' => $this->faker->randomElement($currencies),
            'creation_source' => CreationSource::WEB,
        ];
    }

    public function configure(): Factory
    {
        return $this->afterMaking(function (CompanyRevenue $revenue): void {
            if ($revenue->company !== null) {
                $revenue->team_id = $revenue->company->team_id;
            }
        })->afterCreating(function (CompanyRevenue $revenue): void {
            if ($revenue->company !== null && $revenue->team_id !== $revenue->company->team_id) {
                $revenue->forceFill(['team_id' => $revenue->company->team_id])->saveQuietly();
            }
        })->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
