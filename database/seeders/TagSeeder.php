<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TagSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating tags (200) and attaching to entities...');

        $teams = Team::all();
        $companies = Company::all();
        $people = People::all();
        $opportunities = Opportunity::all();
        $leads = Lead::all();

        if ($teams->isEmpty()) {
            $this->command->warn('No teams found.');

            return;
        }

        $tags = [];
        $tagNames = [
            'VIP', 'Hot Lead', 'Cold Lead', 'Follow Up', 'Qualified', 'Unqualified',
            'Enterprise', 'SMB', 'Startup', 'Partner', 'Competitor', 'Prospect',
            'Customer', 'Former Customer', 'High Value', 'Low Value', 'Priority',
            'On Hold', 'Active', 'Inactive', 'Interested', 'Not Interested',
        ];

        foreach ($teams as $team) {
            foreach ($tagNames as $name) {
                $tags[] = [
                    'team_id' => $team->id,
                    'name' => $name,
                    'slug' => strtolower(str_replace(' ', '-', $name)),
                    'color' => fake()->hexColor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Tag::insert($tags);
        $tagIds = Tag::pluck('id');

        // Attach tags to companies
        if ($companies->isNotEmpty()) {
            $companyTags = [];
            foreach ($companies->random(min(1000, $companies->count())) as $company) {
                $selectedTags = $tagIds->random(random_int(1, 5));
                foreach ($selectedTags as $tagId) {
                    $companyTags[] = [
                        'tag_id' => $tagId,
                        'taggable_type' => \App\Models\Company::class,
                        'taggable_id' => $company->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($companyTags, 1000) as $chunk) {
                DB::table('taggables')->insert($chunk);
            }
        }

        // Attach tags to people
        if ($people->isNotEmpty()) {
            $peopleTags = [];
            foreach ($people->random(min(1000, $people->count())) as $person) {
                $selectedTags = $tagIds->random(random_int(1, 5));
                foreach ($selectedTags as $tagId) {
                    $peopleTags[] = [
                        'tag_id' => $tagId,
                        'taggable_type' => \App\Models\People::class,
                        'taggable_id' => $person->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($peopleTags, 1000) as $chunk) {
                DB::table('taggables')->insert($chunk);
            }
        }

        // Attach tags to opportunities
        if ($opportunities->isNotEmpty()) {
            $opportunityTags = [];
            foreach ($opportunities->random(min(1000, $opportunities->count())) as $opportunity) {
                $selectedTags = $tagIds->random(random_int(1, 5));
                foreach ($selectedTags as $tagId) {
                    $opportunityTags[] = [
                        'tag_id' => $tagId,
                        'taggable_type' => \App\Models\Opportunity::class,
                        'taggable_id' => $opportunity->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($opportunityTags, 1000) as $chunk) {
                DB::table('taggables')->insert($chunk);
            }
        }

        // Attach tags to leads
        if ($leads->isNotEmpty()) {
            $leadTags = [];
            foreach ($leads->random(min(1000, $leads->count())) as $lead) {
                $selectedTags = $tagIds->random(random_int(1, 5));
                foreach ($selectedTags as $tagId) {
                    $leadTags[] = [
                        'tag_id' => $tagId,
                        'taggable_type' => \App\Models\Lead::class,
                        'taggable_id' => $lead->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($leadTags, 1000) as $chunk) {
                DB::table('taggables')->insert($chunk);
            }
        }

        $this->command->info('✓ Created 200 tags and attached to entities');
    }
}
