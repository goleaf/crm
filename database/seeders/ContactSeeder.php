<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Seeder;

final class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating contacts/people (600)...');

        $teams = Team::all();
        $companies = Company::all();

        if ($teams->isEmpty()) {
            $this->command->warn('No teams found. Run UserTeamSeeder first.');

            return;
        }

        People::factory()
            ->count(600)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'company_id' => fn () => $companies->isNotEmpty() ? $companies->random()->id : null,
            ]);

        $this->command->info('✓ Created 600 contacts/people');
    }
}
