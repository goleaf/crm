<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating activities (2000)...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $people = People::all();
        $opportunities = Opportunity::all();
        $leads = Lead::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $activities = [];
        $entities = [
            ['type' => \App\Models\Company::class, 'ids' => $companies->pluck('id')->toArray()],
            ['type' => \App\Models\People::class, 'ids' => $people->pluck('id')->toArray()],
            ['type' => \App\Models\Opportunity::class, 'ids' => $opportunities->pluck('id')->toArray()],
            ['type' => \App\Models\Lead::class, 'ids' => $leads->pluck('id')->toArray()],
        ];

        for ($i = 0; $i < 2000; $i++) {
            $entity = fake()->randomElement($entities);

            $activities[] = [
                'team_id' => $teams->random()->id,
                'user_id' => $users->random()->id,
                'subject_type' => $entity['type'],
                'subject_id' => empty($entity['ids']) ? null : fake()->randomElement($entity['ids']),
                'type' => fake()->randomElement(['call', 'email', 'meeting', 'note', 'task']),
                'description' => fake()->sentence(),
                'occurred_at' => now()->subDays(random_int(0, 365)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($activities, 1000) as $chunk) {
            Activity::insert($chunk);
        }

        $this->command->info('✓ Created 2000 activities');
    }
}
