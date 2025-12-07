<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class UserTeamSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating users and teams (100 users, 10 teams)...');

        // Create main owner with personal team
        $owner = User::factory()
            ->withPersonalTeam()
            ->create([
                'name' => 'System Owner',
                'email' => 'owner@example.com',
            ]);

        // Create additional teams
        $teams = Team::factory()
            ->count(9)
            ->create();

        $teams->prepend($owner->personalTeam());

        // Create users in bulk
        $users = User::factory()
            ->count(99)
            ->create();

        $users->prepend($owner);

        // Attach users to teams efficiently (ensuring unique combinations)
        $attachments = [];
        $usedCombinations = [];

        foreach ($users as $user) {
            $userTeams = $teams->random(random_int(1, 3));
            foreach ($userTeams as $team) {
                $key = $user->id.'-'.$team->id;
                if (! isset($usedCombinations[$key])) {
                    $attachments[] = [
                        'user_id' => $user->id,
                        'team_id' => $team->id,
                        'role' => fake()->randomElement(['admin', 'member', 'viewer']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $usedCombinations[$key] = true;
                }
            }
        }

        DB::table('team_user')->insert($attachments);

        // Create social accounts for some users (50% of users)
        $socialAccounts = [];
        foreach ($users->random(min(50, $users->count())) as $user) {
            $socialAccounts[] = [
                'user_id' => $user->id,
                'provider_name' => fake()->randomElement(['google', 'github', 'linkedin']),
                'provider_id' => fake()->uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        UserSocialAccount::insert($socialAccounts);

        // Create team invitations
        $invitations = [];
        foreach ($teams->random(min(5, $teams->count())) as $team) {
            for ($i = 0; $i < random_int(1, 3); $i++) {
                $invitations[] = [
                    'team_id' => $team->id,
                    'email' => fake()->unique()->safeEmail(),
                    'role' => fake()->randomElement(['member', 'viewer']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        TeamInvitation::insert($invitations);

        $this->command->info('✓ Created '.$users->count().' users and '.$teams->count().' teams');
    }
}
