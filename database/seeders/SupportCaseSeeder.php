<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class SupportCaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating support cases (300)...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $people = People::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        SupportCase::factory()
            ->count(300)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'company_id' => fn () => $companies->isNotEmpty() ? $companies->random()->id : null,
                'contact_id' => fn () => $people->isNotEmpty() ? $people->random()->id : null,
                'assigned_to_id' => fn () => $users->random()->id,
            ]);

        $this->command->info('✓ Created 300 support cases');
    }
}
