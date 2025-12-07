<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

final class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating vendors (500)...');

        $teams = Team::all();

        if ($teams->isEmpty()) {
            $this->command->warn('No teams found. Run UserTeamSeeder first.');

            return;
        }

        Vendor::factory()
            ->count(500)
            ->create([
                'team_id' => fn () => $teams->random()->id,
            ]);

        $this->command->info('✓ Created 500 vendors');
    }
}
