<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
final class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountTypes = AccountType::cases();
        $ownershipTypes = array_keys(config('company.ownership_types', []));
        $industries = Industry::cases();
        $currencies = array_keys(config('company.currency_codes', []));

        return [
            'name' => $this->faker->company(),
            'account_owner_id' => User::factory(),
            'team_id' => Team::factory(),
            'account_type' => $this->faker->randomElement($accountTypes)->value,
            'ownership' => $this->faker->randomElement($ownershipTypes),
            'phone' => $this->faker->e164PhoneNumber(),
            'primary_email' => $this->faker->companyEmail(),
            'website' => $this->faker->url(),
            'industry' => $this->faker->randomElement($industries)->value,
            'revenue' => $this->faker->randomFloat(2, 100000, 100000000),
            'employee_count' => $this->faker->numberBetween(5, 5000),
            'currency_code' => $this->faker->randomElement($currencies),
            'billing_street' => $this->faker->streetAddress(),
            'billing_city' => $this->faker->city(),
            'billing_state' => $this->faker->stateAbbr(),
            'billing_postal_code' => $this->faker->numerify('#####'),
            'billing_country' => 'US',
            'shipping_street' => $this->faker->streetAddress(),
            'shipping_city' => $this->faker->city(),
            'shipping_state' => $this->faker->stateAbbr(),
            'shipping_postal_code' => $this->faker->numerify('#####'),
            'shipping_country' => 'US',
            'social_links' => [
                'linkedin' => $this->faker->url(),
                'twitter' => $this->faker->url(),
            ],
            'description' => $this->faker->paragraph(),
            'addresses' => [
                [
                    'type' => AddressType::BILLING->value,
                    'line1' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->stateAbbr(),
                    'postal_code' => $this->faker->numerify('#####'),
                    'country_code' => 'US',
                ],
                [
                    'type' => AddressType::SHIPPING->value,
                    'line1' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->stateAbbr(),
                    'postal_code' => $this->faker->numerify('#####'),
                    'country_code' => 'US',
                ],
            ],
        ];
    }

    public function configure(): Factory
    {
        // Use minutes instead of seconds to ensure distinct timestamps
        // and avoid flaky sorting tests in fast CI environments
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
