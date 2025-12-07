<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\People>
 */
final class PeopleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'team_id' => Team::factory(),
            'primary_email' => fake()->safeEmail(),
            'alternate_email' => fake()->safeEmail(),
            'phone_mobile' => fake()->e164PhoneNumber(),
            'phone_office' => fake()->phoneNumber(),
            'phone_home' => null,
            'phone_fax' => null,
            'job_title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Sales', 'Marketing', 'Product', 'Operations']),
            'role' => fake()->randomElement([
                'Decision Maker',
                'Technical Contact',
                'Billing Contact',
                'Champion',
                'Influencer',
            ]),
            'birthdate' => fake()->dateTimeBetween('-65 years', '-21 years'),
            'assistant_name' => fake()->name(),
            'assistant_phone' => fake()->phoneNumber(),
            'assistant_email' => fake()->safeEmail(),
            'address_street' => fake()->streetAddress(),
            'address_city' => fake()->city(),
            'address_state' => fake()->state(),
            'address_postal_code' => fake()->postcode(),
            'address_country' => fake()->countryCode(),
            'social_links' => [
                'linkedin' => fake()->url(),
            ],
            'lead_source' => fake()->randomElement(['Website', 'Referral', 'Event', 'Outbound', 'Partner']),
            'is_portal_user' => false,
            'sync_enabled' => false,
            'segments' => fake()->randomElements(['VIP', 'Prospect', 'Customer', 'Champion'], 2),
        ];
    }

    public function configure(): Factory
    {
        // Use minutes instead of seconds to ensure distinct timestamps
        // and avoid flaky sorting tests in fast CI environments
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ])->afterCreating(function (\App\Models\People $people): void {
            // Email records are automatically created by PeopleObserver::saved()
            // via ensureEmailsFromColumns() method, which handles both primary_email
            // and alternate_email columns
        });
    }
}
