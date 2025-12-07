<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PdfTemplate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PdfTemplate>
 */
final class PdfTemplateFactory extends Factory
{
    protected $model = PdfTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => fake()->words(3, true),
            'key' => fake()->unique()->slug(),
            'entity_type' => fake()->randomElement([\App\Models\Invoice::class, \App\Models\Opportunity::class, \App\Models\SupportCase::class]),
            'description' => fake()->sentence(),
            'layout' => $this->generateSampleLayout(),
            'merge_fields' => [
                'company_name',
                'contact_name',
                'date',
                'amount',
                'description',
            ],
            'styling' => [
                'paper_size' => 'A4',
                'orientation' => 'portrait',
                'margins' => [
                    'top' => 10,
                    'right' => 10,
                    'bottom' => 10,
                    'left' => 10,
                ],
            ],
            'watermark' => null,
            'permissions' => null,
            'encryption_enabled' => false,
            'encryption_password' => null,
            'version' => 1,
            'parent_template_id' => null,
            'is_active' => true,
            'is_archived' => false,
            'archived_at' => null,
            'metadata' => null,
        ];
    }

    public function withWatermark(): self
    {
        return $this->state(fn (array $attributes): array => [
            'watermark' => [
                'text' => 'CONFIDENTIAL',
                'opacity' => 0.3,
                'rotation' => -45,
                'font_size' => 72,
                'color' => '#cccccc',
            ],
        ]);
    }

    public function withEncryption(): self
    {
        return $this->state(fn (array $attributes): array => [
            'encryption_enabled' => true,
            'encryption_password' => 'secret123',
        ]);
    }

    public function withPermissions(): self
    {
        return $this->state(fn (array $attributes): array => [
            'permissions' => [
                'users' => [1, 2, 3],
                'roles' => ['admin', 'manager'],
            ],
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_archived' => true,
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    private function generateSampleLayout(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Document</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .content { margin: 20px 0; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{company_name}}</h1>
    </div>
    <div class="content">
        <p><strong>Contact:</strong> {{contact_name}}</p>
        <p><strong>Date:</strong> {{date}}</p>
        <p><strong>Amount:</strong> {{amount}}</p>
        <p><strong>Description:</strong> {{description}}</p>
    </div>
    <div class="footer">
        <p>Generated on {{date}}</p>
    </div>
</body>
</html>
HTML;
    }
}
