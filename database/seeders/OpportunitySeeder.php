<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating opportunities (500)...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $people = People::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found. Run UserTeamSeeder first.');

            return;
        }

        $opportunities = Opportunity::factory()
            ->count(500)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'company_id' => fn () => $companies->isNotEmpty() ? $companies->random()->id : null,
                'contact_id' => fn () => $people->isNotEmpty() ? $people->random()->id : null,
            ]);

        // Attach collaborators efficiently
        $collaborations = [];
        foreach ($opportunities as $opportunity) {
            $collaboratorCount = random_int(1, 4);
            $collaborators = $users->random($collaboratorCount);

            foreach ($collaborators as $collaborator) {
                $collaborations[] = [
                    'opportunity_id' => $opportunity->id,
                    'user_id' => $collaborator->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('opportunity_user')->insert($collaborations);

        $this->command->info('✓ Created '.$opportunities->count().' opportunities with collaborators');
    }
}
