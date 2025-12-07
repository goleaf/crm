<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailProgram;
use App\Models\EmailProgramStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailProgramStep>
 */
final class EmailProgramStepFactory extends Factory
{
    protected $model = EmailProgramStep::class;

    public function definition(): array
    {
        return [
            'email_program_id' => EmailProgram::factory(),
            'step_order' => 0,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'subject_line' => fake()->sentence(),
            'preview_text' => fake()->sentence(),
            'html_content' => '<p>Hello {{first_name}},</p><p>'.fake()->paragraph().'</p>',
            'plain_text_content' => fake()->paragraph(),
            'from_name' => fake()->name(),
            'from_email' => fake()->safeEmail(),
            'reply_to_email' => fake()->safeEmail(),
            'variant_name' => null,
            'is_control' => false,
            'delay_value' => 0,
            'delay_unit' => 'days',
            'conditional_send_rules' => null,
            'recipients_count' => 0,
            'sent_count' => 0,
            'delivered_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'bounced_count' => 0,
            'unsubscribed_count' => 0,
        ];
    }

    public function asVariant(string $variantName, bool $isControl = false): self
    {
        return $this->state(fn (array $attributes): array => [
            'variant_name' => $variantName,
            'is_control' => $isControl,
        ]);
    }

    public function withDelay(int $value, string $unit = 'days'): self
    {
        return $this->state(fn (array $attributes): array => [
            'delay_value' => $value,
            'delay_unit' => $unit,
        ]);
    }
}
