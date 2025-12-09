<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\Team;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
final class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->company(),
            'status' => VendorStatus::ACTIVE,
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->unique()->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'payment_terms' => 'Net 30',
            'shipping_terms' => 'Standard',
            'ship_method' => 'Ground',
            'preferred_currency' => config('company.default_currency', 'USD'),
        ];
    }
}
