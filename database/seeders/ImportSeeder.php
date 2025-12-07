<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Import;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class ImportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating imports (100)...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $imports = [];
        for ($i = 0; $i < 100; $i++) {
            $imports[] = [
                'team_id' => $teams->random()->id,
                'user_id' => $users->random()->id,
                'model_type' => fake()->randomElement([\App\Models\Company::class, \App\Models\People::class, \App\Models\Lead::class, \App\Models\Opportunity::class]),
                'file_name' => fake()->word().'.csv',
                'file_path' => 'imports/'.fake()->uuid().'.csv',
                'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
                'total_rows' => random_int(10, 10000),
                'processed_rows' => fake()->boolean(80) ? random_int(10, 10000) : 0,
                'successful_rows' => fake()->boolean(80) ? random_int(10, 9000) : 0,
                'failed_rows' => fake()->boolean(30) ? random_int(0, 1000) : 0,
                'error_log' => fake()->boolean(20) ? json_encode(['errors' => ['Row 5: Invalid email']]) : null,
                'started_at' => now()->subDays(random_int(1, 180)),
                'completed_at' => fake()->boolean(80) ? now()->subDays(random_int(0, 179)) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Import::insert($imports);

        $this->command->info('✓ Created 100 imports');
    }
}
