<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AiSummary;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Seeder;

final class AiSummarySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating AI summaries (1000)...');

        $teams = Team::all();
        $companies = Company::all();
        $people = People::all();
        $opportunities = Opportunity::all();
        $leads = Lead::all();

        if ($teams->isEmpty()) {
            $this->command->warn('No teams found.');

            return;
        }

        $summaries = [];
        $entities = [
            ['type' => \App\Models\Company::class, 'ids' => $companies->pluck('id')->toArray()],
            ['type' => \App\Models\People::class, 'ids' => $people->pluck('id')->toArray()],
            ['type' => \App\Models\Opportunity::class, 'ids' => $opportunities->pluck('id')->toArray()],
            ['type' => \App\Models\Lead::class, 'ids' => $leads->pluck('id')->toArray()],
        ];

        for ($i = 0; $i < 1000; $i++) {
            $entity = fake()->randomElement($entities);

            if (! empty($entity['ids'])) {
                $summaries[] = [
                    'team_id' => $teams->random()->id,
                    'summarizable_type' => $entity['type'],
                    'summarizable_id' => fake()->randomElement($entity['ids']),
                    'summary' => fake()->paragraphs(2, true),
                    'key_points' => json_encode([
                        fake()->sentence(),
                        fake()->sentence(),
                        fake()->sentence(),
                    ]),
                    'sentiment' => fake()->randomElement(['positive', 'neutral', 'negative']),
                    'confidence_score' => fake()->randomFloat(2, 0.5, 1.0),
                    'generated_at' => now()->subDays(random_int(1, 90)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($summaries, 500) as $chunk) {
            AiSummary::insert($chunk);
        }

        $this->command->info('✓ Created 1000 AI summaries');
    }
}
