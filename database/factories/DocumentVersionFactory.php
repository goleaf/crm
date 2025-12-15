<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
final class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'team_id' => null,
            'uploaded_by' => User::factory(),
            'version' => 1,
            'file_path' => 'documents/example.txt',
            'disk' => 'public',
            'notes' => $this->faker->sentence(),
        ];
    }
}
