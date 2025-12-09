<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
final class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        $accountTypes = AccountType::cases();
        $industries = Industry::cases();
        $currencies = array_keys(config('company.currency_codes', []));

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'team_id' => Team::factory(),
            'type' => fake()->randomElement($accountTypes)?->value ?? AccountType::CUSTOMER->value,
            'industry' => fake()->randomElement($industries)?->value ?? Industry::OTHER->value,
            'annual_revenue' => fake()->randomFloat(2, 10_000, 100_000_000),
            'employee_count' => fake()->numberBetween(1, 10_000),
            'currency' => fake()->randomElement($currencies) ?: config('company.default_currency', 'USD'),
            'website' => fake()->url(),
            'social_links' => [
                'twitter' => 'https://twitter.com/' . fake()->userName(),
                'facebook' => 'https://facebook.com/' . fake()->userName(),
                'linkedin' => 'https://linkedin.com/company/' . Str::slug($name),
            ],
            'billing_address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->numerify('#####'),
                'country' => 'US',
            ],
            'shipping_address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->numerify('#####'),
                'country' => 'US',
            ],
            'addresses' => [
                [
                    'type' => AddressType::BILLING->value,
                    'line1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->stateAbbr(),
                    'postal_code' => fake()->numerify('#####'),
                    'country_code' => 'US',
                ],
                [
                    'type' => AddressType::SHIPPING->value,
                    'line1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->stateAbbr(),
                    'postal_code' => fake()->numerify('#####'),
                    'country_code' => 'US',
                ],
            ],
            'custom_fields' => ['rating' => fake()->numberBetween(1, 5)],
            'owner_id' => User::factory(),
            'assigned_to_id' => null,
            'parent_id' => null,
        ];
    }

    public function configure(): Factory
    {
        return $this->afterCreating(function (Account $account): void {
            if ($account->owner === null || $account->team === null) {
                return;
            }

            $account->owner->teams()->syncWithoutDetaching($account->team);

            if ($account->owner->currentTeam === null) {
                $account->owner->forceFill(['current_team_id' => $account->team->getKey()])->save();
            }
        });
    }
}
