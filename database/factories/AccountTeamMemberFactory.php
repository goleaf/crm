<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use App\Models\AccountTeamMember;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountTeamMember>
 */
final class AccountTeamMemberFactory extends Factory
{
    protected $model = AccountTeamMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $team = Team::factory();

        return [
            'company_id' => Company::factory()->for($team),
            'team_id' => $team,
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement(AccountTeamRole::cases())->value,
            'access_level' => $this->faker->randomElement(AccountTeamAccessLevel::cases())->value,
        ];
    }

    public function configure(): Factory
    {
        return $this->afterCreating(function (AccountTeamMember $member): void {
            if ($member->team !== null) {
                $member->team->users()->syncWithoutDetaching($member->user_id);
            }
        });
    }
}
