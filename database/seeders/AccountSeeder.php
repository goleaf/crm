<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountTeamMember;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating accounts with all relations...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found. Run UserTeamSeeder first.');

            return;
        }

        // Create 50 accounts with full relations
        $accounts = collect();

        for ($i = 0; $i < 50; $i++) {
            $team = $teams->random();
            $owner = $users->random();

            $account = Account::factory()->create([
                'team_id' => $team->id,
                'owner_id' => $owner->id,
                'assigned_to_id' => $users->random()->id,
            ]);

            // Create 2-5 contacts per account
            $contactCount = random_int(2, 5);
            $contacts = People::factory()
                ->count($contactCount)
                ->create([
                    'team_id' => $team->id,
                    'company_id' => null, // Will be linked via pivot
                ]);

            // Attach contacts to account with pivot data
            foreach ($contacts as $index => $contact) {
                $account->contacts()->attach($contact->id, [
                    'is_primary' => $index === 0,
                    'role' => fake()->randomElement(['Decision Maker', 'Technical Contact', 'Billing Contact']),
                ]);
            }

            // Create 1-3 opportunities per account
            $opportunityCount = random_int(1, 3);
            Opportunity::factory()
                ->count($opportunityCount)
                ->create([
                    'account_id' => $account->id,
                    'team_id' => $team->id,
                    'contact_id' => $contacts->random()->id,
                ]);

            // Create 0-2 support cases per account
            if (random_int(0, 1) !== 0) {
                $caseCount = random_int(1, 2);
                SupportCase::factory()
                    ->count($caseCount)
                    ->create([
                        'account_id' => $account->id,
                        'team_id' => $team->id,
                        'contact_id' => $contacts->random()->id,
                    ]);
            }

            // Create 2-4 notes per account
            $noteCount = random_int(2, 4);
            $notes = Note::factory()
                ->count($noteCount)
                ->create([
                    'team_id' => $team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($notes as $note) {
                $account->notes()->attach($note->id);
            }

            // Create 1-3 tasks per account
            $taskCount = random_int(1, 3);
            $tasks = Task::factory()
                ->count($taskCount)
                ->create([
                    'team_id' => $team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $account->tasks()->attach($task->id);
            }

            // Create 1-3 team members per account
            $teamMemberCount = random_int(1, 3);
            $availableUsers = $users->shuffle()->take($teamMemberCount);

            foreach ($availableUsers as $user) {
                // Check if this combination already exists
                $exists = AccountTeamMember::where('company_id', $account->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $exists) {
                    AccountTeamMember::factory()->create([
                        'company_id' => $account->id,
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                    ]);
                }
            }

            // Create parent-child relationships for some accounts
            if ($i > 0 && random_int(0, 3) === 0) {
                $potentialParent = $accounts->random();
                if (! $account->wouldCreateCycle($potentialParent->id)) {
                    $account->update(['parent_id' => $potentialParent->id]);
                }
            }

            $accounts->push($account);

            if (($i + 1) % 10 === 0) {
                $count = $i + 1;
                $this->command->info("  Created {$count} accounts...");
            }
        }

        $this->command->info('✓ Created '.$accounts->count().' accounts with contacts, opportunities, cases, notes, tasks, and team members');
    }
}
