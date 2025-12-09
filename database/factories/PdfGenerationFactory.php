<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PdfGenerationStatus;
use App\Models\Invoice;
use App\Models\PdfGeneration;
use App\Models\PdfTemplate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PdfGeneration>
 */
final class PdfGenerationFactory extends Factory
{
    protected $model = PdfGeneration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'pdf_template_id' => PdfTemplate::factory(),
            'user_id' => User::factory(),
            'entity_type' => Invoice::class,
            'entity_id' => Invoice::factory(),
            'file_path' => 'pdfs/team-1/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->uuid() . '.pdf',
            'file_size' => fake()->numberBetween(10000, 500000),
            'page_count' => fake()->numberBetween(1, 10),
            'merge_data' => [
                'company_name' => fake()->company(),
                'contact_name' => fake()->name(),
                'date' => now()->format('Y-m-d'),
                'amount' => fake()->randomFloat(2, 100, 10000),
            ],
            'generation_options' => [],
            'has_watermark' => false,
            'is_encrypted' => false,
            'status' => PdfGenerationStatus::COMPLETED,
            'error_message' => null,
            'generated_at' => now(),
        ];
    }

    public function withWatermark(): self
    {
        return $this->state(fn (array $attributes): array => [
            'has_watermark' => true,
        ]);
    }

    public function encrypted(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_encrypted' => true,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PdfGenerationStatus::FAILED,
            'error_message' => 'PDF generation failed: ' . fake()->sentence(),
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PdfGenerationStatus::PENDING,
        ]);
    }

    public function processing(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PdfGenerationStatus::PROCESSING,
        ]);
    }
}
