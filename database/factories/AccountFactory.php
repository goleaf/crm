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
 * Factory for creating Account model instances.
 *
 * Generates realistic account data including company information,
 * addresses (billing/shipping), social links, and custom fields.
 * Automatically creates associated Team and User (owner) relationships.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 *
 * @see \App\Models\Account
 * @see \Tests\Unit\Factories\AccountFactoryTest
 */
final class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Generates a complete account with:
     * - Company name and unique slug
     * - Random account type and industry from enums
     * - Financial data (annual revenue, employee count, currency)
     * - Website and social media links
     * - Billing and shipping addresses with US postal codes
     * - Custom fields with rating
     * - Associated team and owner relationships
     *
     * @return array<string, mixed> The default attribute values for the Account model
     */
    public function definition(): array
    {
        $name = fake()->company();
        $accountTypes = AccountType::cases();
        $industries = Industry::cases();
        $currencies = array_keys(config('company.currency_codes', []));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'team_id' => Team::factory(),
            'type' => fake()->randomElement($accountTypes)?->value ?? AccountType::CUSTOMER->value,
            'industry' => fake()->randomElement($industries)?->value ?? Industry::OTHER->value,
            'annual_revenue' => fake()->randomFloat(2, 10_000, 100_000_000),
            'employee_count' => fake()->numberBetween(1, 10_000),
            'currency' => fake()->randomElement($currencies) ?: config('company.default_currency', 'USD'),
            'website' => fake()->url(),
            'social_links' => [
                'twitter' => 'https://twitter.com/'.fake()->userName(),
                'facebook' => 'https://facebook.com/'.fake()->userName(),
                'linkedin' => 'https://linkedin.com/company/'.Str::slug($name),
            ],
            'billing_address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => (string) fake()->numberBetween(10000, 99999),
                'country' => 'US',
            ],
            'shipping_address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => (string) fake()->numberBetween(10000, 99999),
                'country' => 'US',
            ],
            'addresses' => [
                [
                    'type' => AddressType::BILLING->value,
                    'line1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->stateAbbr(),
                    'postal_code' => (string) fake()->numberBetween(10000, 99999),
                    'country_code' => 'US',
                ],
                [
                    'type' => AddressType::SHIPPING->value,
                    'line1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->stateAbbr(),
                    'postal_code' => (string) fake()->numberBetween(10000, 99999),
                    'country_code' => 'US',
                ],
            ],
            'custom_fields' => ['rating' => fake()->numberBetween(1, 5)],
            'owner_id' => User::factory(),
            'assigned_to_id' => null,
            'parent_id' => null,
        ];
    }

    /**
     * Configure the factory with after-creating callbacks.
     *
     * Ensures the account owner is properly attached to the account's team
     * and sets the owner's current team if not already set.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
     */
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
