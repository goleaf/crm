<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactEmailType;
use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PeopleEmail>
 */
final class PeopleEmailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'people_id' => People::factory(),
            'email' => fake()->unique()->safeEmail(),
            'type' => fake()->randomElement(ContactEmailType::cases()),
            'is_primary' => false,
        ];
    }
}
