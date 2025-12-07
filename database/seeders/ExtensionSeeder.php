<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Extension;
use App\Models\ExtensionExecution;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class ExtensionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating extensions (50) with executions...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $extensions = Extension::factory()
            ->count(50)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'creator_id' => fn () => $users->random()->id,
            ]);

        $executions = [];
        foreach ($extensions as $extension) {
            for ($i = 0; $i < random_int(10, 50); $i++) {
                $executions[] = [
                    'extension_id' => $extension->id,
                    'team_id' => $extension->team_id,
                    'triggered_by' => $users->random()->id,
                    'status' => fake()->randomElement(['pending', 'running', 'completed', 'failed']),
                    'started_at' => now()->subDays(random_int(1, 180)),
                    'completed_at' => fake()->boolean(80) ? now()->subDays(random_int(0, 179)) : null,
                    'input' => json_encode(['param' => 'value']),
                    'output' => json_encode(['result' => 'success']),
                    'error_message' => fake()->boolean(10) ? fake()->sentence() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($executions, 500) as $chunk) {
            ExtensionExecution::insert($chunk);
        }

        $this->command->info('✓ Created 50 extensions with executions');
    }
}
