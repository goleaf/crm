<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentShare>
 */
final class DocumentShareFactory extends Factory
{
    protected $model = DocumentShare::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'team_id' => null,
            'user_id' => User::factory(),
            'permission' => 'view',
        ];
    }
}
