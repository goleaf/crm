<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Note;
use App\Models\NoteHistory;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class NoteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating notes (1000) with history...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $people = People::all();
        $opportunities = Opportunity::all();
        $leads = Lead::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $notes = Note::factory()
            ->count(1000)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'creator_id' => fn () => $users->random()->id,
            ]);

        // Attach notes to companies
        $companyNotes = [];
        foreach ($notes->random(2000) as $note) {
            $companyNotes[] = [
                'note_id' => $note->id,
                'company_id' => $companies->random()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('company_note')->insert($companyNotes);

        // Attach notes to people
        $peopleNotes = [];
        foreach ($notes->random(2000) as $note) {
            $peopleNotes[] = [
                'note_id' => $note->id,
                'people_id' => $people->random()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('note_people')->insert($peopleNotes);

        // Attach notes to opportunities
        $opportunityNotes = [];
        foreach ($notes->random(1500) as $note) {
            $opportunityNotes[] = [
                'note_id' => $note->id,
                'opportunity_id' => $opportunities->random()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('note_opportunity')->insert($opportunityNotes);

        // Attach notes to leads
        $leadNotes = [];
        foreach ($notes->random(1500) as $note) {
            $leadNotes[] = [
                'note_id' => $note->id,
                'lead_id' => $leads->random()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('lead_note')->insert($leadNotes);

        // Create note history
        $history = [];
        foreach ($notes->random(2000) as $note) {
            for ($i = 0; $i < random_int(1, 5); $i++) {
                $history[] = [
                    'note_id' => $note->id,
                    'team_id' => $note->team_id,
                    'user_id' => $users->random()->id,
                    'action' => fake()->randomElement(['created', 'updated', 'deleted']),
                    'old_content' => fake()->paragraph(),
                    'new_content' => fake()->paragraph(),
                    'created_at' => now()->subDays(random_int(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($history, 1000) as $chunk) {
            NoteHistory::insert($chunk);
        }

        $this->command->info('✓ Created 1000 notes with history');
    }
}
