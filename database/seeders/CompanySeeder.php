<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyRevenue;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating companies (400) with revenues...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found. Run UserTeamSeeder first.');

            return;
        }

        $companies = Company::factory()
            ->count(400)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'account_owner_id' => fn () => $users->random()->id,
            ]);

        // Create company revenues in bulk
        $revenues = [];
        foreach ($companies as $company) {
            $years = range(2018, 2025);
            shuffle($years);
            $revenueCount = random_int(3, 8);

            for ($i = 0; $i < $revenueCount; $i++) {
                $revenues[] = [
                    'company_id' => $company->id,
                    'team_id' => $company->team_id,
                    'year' => $years[$i],
                    'amount' => fake()->randomFloat(2, 100000, 50000000),
                    'currency_code' => fake()->randomElement(['USD', 'EUR', 'GBP']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert in chunks for performance
        foreach (array_chunk($revenues, 1000) as $chunk) {
            CompanyRevenue::insert($chunk);
        }

        $this->command->info('✓ Created '.$companies->count().' companies with '.count($revenues).' revenue records');
    }
}
