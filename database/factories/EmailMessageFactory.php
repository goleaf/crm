<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailMessage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailMessage>
 */
final class EmailMessageFactory extends Factory
{
    protected $model = EmailMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'subject' => fake()->sentence(6),
            'body_html' => fake()->randomHtml(),
            'body_text' => fake()->paragraph(5),
            'from_email' => fake()->safeEmail(),
            'from_name' => fake()->name(),
            'to_emails' => [
                ['email' => fake()->safeEmail(), 'name' => fake()->name()],
            ],
            'status' => fake()->randomElement(['draft', 'sent', 'delivered', 'failed']),
            'importance' => fake()->randomElement(['low', 'normal', 'high']),
            'read_receipt_requested' => fake()->boolean(10),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 week'),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'sent',
            'sent_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
