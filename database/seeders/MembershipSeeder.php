<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating memberships (500)...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $memberships = [];
        for ($i = 0; $i < 500; $i++) {
            $memberships[] = [
                'team_id' => $teams->random()->id,
                'user_id' => $users->random()->id,
                'membership_type' => fake()->randomElement(['basic', 'premium', 'enterprise']),
                'status' => fake()->randomElement(['active', 'expired', 'cancelled']),
                'starts_at' => now()->subDays(random_int(30, 730)),
                'expires_at' => now()->addDays(random_int(30, 365)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($memberships, 500) as $chunk) {
            Membership::insert($chunk);
        }

        $this->command->info('✓ Created 500 memberships');
    }
}
