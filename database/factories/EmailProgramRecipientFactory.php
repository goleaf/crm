<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmailSendStatus;
use App\Models\EmailProgram;
use App\Models\EmailProgramRecipient;
use App\Models\EmailProgramStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailProgramRecipient>
 */
final class EmailProgramRecipientFactory extends Factory
{
    protected $model = EmailProgramRecipient::class;

    public function definition(): array
    {
        return [
            'email_program_id' => EmailProgram::factory(),
            'email_program_step_id' => EmailProgramStep::factory(),
            'email' => fake()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'custom_fields' => null,
            'recipient_type' => null,
            'recipient_id' => null,
            'status' => EmailSendStatus::PENDING,
            'scheduled_send_at' => now()->addHour(),
            'open_count' => 0,
            'click_count' => 0,
            'engagement_score' => 0,
        ];
    }

    public function sent(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailSendStatus::SENT,
            'sent_at' => now()->subHours(2),
        ]);
    }

    public function delivered(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailSendStatus::DELIVERED,
            'sent_at' => now()->subHours(2),
            'delivered_at' => now()->subHours(1),
        ]);
    }

    public function opened(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailSendStatus::DELIVERED,
            'sent_at' => now()->subHours(2),
            'delivered_at' => now()->subHours(1),
            'opened_at' => now()->subMinutes(30),
            'open_count' => 1,
            'engagement_score' => 5,
        ]);
    }

    public function clicked(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailSendStatus::DELIVERED,
            'sent_at' => now()->subHours(2),
            'delivered_at' => now()->subHours(1),
            'opened_at' => now()->subMinutes(30),
            'clicked_at' => now()->subMinutes(20),
            'open_count' => 1,
            'click_count' => 1,
            'engagement_score' => 15,
        ]);
    }

    public function bounced(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailSendStatus::BOUNCED,
            'sent_at' => now()->subHours(2),
            'bounced_at' => now()->subHours(1),
            'bounce_type' => 'hard',
            'bounce_reason' => 'Mailbox does not exist',
        ]);
    }

    public function unsubscribed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailSendStatus::UNSUBSCRIBED,
            'unsubscribed_at' => now()->subHours(1),
        ]);
    }
}
