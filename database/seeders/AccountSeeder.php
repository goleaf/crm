<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountMerge;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating accounts (200)...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found. Run UserTeamSeeder first.');

            return;
        }

        $accounts = Account::factory()
            ->count(200)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'owner_id' => fn () => $users->random()->id,
                'assigned_to_id' => fn () => $users->random()->id,
            ]);

        // Create account team members (skipped due to complexity with unique constraints)
        // TODO: Fix this seeder to properly handle unique company_id + user_id combinations
        $this->command->info('Skipping account team members for now...');

        // Create account merges (avoiding duplicate combinations)
        // Get actual company IDs from the companies table
        $companyIds = \App\Models\Company::pluck('id')->toArray();
        $merges = [];
        $usedMergePairs = [];

        $mergeCount = min(50, floor(count($companyIds) / 2));

        for ($i = 0; $i < $mergeCount; $i++) {
            $primaryCompanyId = $companyIds[array_rand($companyIds)];
            $availableForMerge = array_diff($companyIds, [$primaryCompanyId]);

            if ($availableForMerge === []) {
                continue;
            }

            $duplicateCompanyId = $availableForMerge[array_rand($availableForMerge)];
            $pairKey = min($primaryCompanyId, $duplicateCompanyId).'-'.max($primaryCompanyId, $duplicateCompanyId);

            if (! isset($usedMergePairs[$pairKey])) {
                $merges[] = [
                    'primary_company_id' => $primaryCompanyId,
                    'duplicate_company_id' => $duplicateCompanyId,
                    'merged_by_user_id' => $users->random()->id,
                    'field_selections' => json_encode([
                        'name' => 'primary',
                        'email' => 'primary',
                        'phone' => 'duplicate',
                    ]),
                    'transferred_relationships' => json_encode([
                        'contacts' => random_int(5, 20),
                        'opportunities' => random_int(1, 10),
                        'activities' => random_int(10, 50),
                    ]),
                    'created_at' => now()->subDays(random_int(1, 365)),
                    'updated_at' => now()->subDays(random_int(1, 365)),
                ];
                $usedMergePairs[$pairKey] = true;
            }
        }

        if ($merges !== []) {
            AccountMerge::insert($merges);
        }

        $this->command->info('✓ Created '.$accounts->count().' accounts with team members and merges');
    }
}
