<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Team;
use App\Models\Territory;
use App\Models\TerritoryAssignment;
use App\Models\TerritoryOverlap;
use App\Models\TerritoryQuota;
use App\Models\TerritoryRecord;
use App\Models\TerritoryTransfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class TerritorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating territories with assignments and quotas...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $leads = Lead::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        // Create a few placeholder companies/leads if none exist
        if ($companies->isEmpty()) {
            $companies = Company::factory()->count(5)->create();
        }

        if ($leads->isEmpty()) {
            $leads = Lead::factory()->count(5)->create();
        }

        $territories = collect();
        foreach ($teams as $team) {
            $territories = $territories->merge(
                Territory::factory()
                    ->count(3)
                    ->create([
                        'team_id' => $team->id,
                        'name' => fn () => fake()->city(),
                        'code' => fn () => Str::upper(Str::random(5)),
                        'type' => fake()->randomElement(['geographic', 'product', 'hybrid']),
                        'assignment_rules' => ['country' => fake()->country()],
                        'quota_period' => 'quarterly',
                        'revenue_quota' => fake()->randomFloat(2, 50_000, 500_000),
                    ])
            );
        }

        // Create assignments
        $assignments = [];
        foreach ($territories as $territory) {
            $assignees = $users->shuffle()->take(min($users->count(), random_int(2, 5)))->values();

            foreach ($assignees as $index => $user) {
                $assignments[] = [
                    'territory_id' => $territory->id,
                    'user_id' => $user->id,
                    'role' => fake()->randomElement(['owner', 'member', 'viewer']),
                    'is_primary' => $index === 0,
                    'start_date' => now()->subDays(random_int(1, 365))->toDateString(),
                    'end_date' => null,
                ];
            }
        }

        TerritoryAssignment::insert($assignments);

        // Create quotas
        $quotas = [];
        foreach ($territories as $territory) {
            for ($i = 0; $i < 2; $i++) {
                $quotas[] = [
                    'territory_id' => $territory->id,
                    'period' => now()->format('Y').'-Q'.($i + 1),
                    'revenue_target' => fake()->randomFloat(2, 50_000, 250_000),
                    'unit_target' => random_int(10, 50),
                    'revenue_actual' => fake()->randomFloat(2, 10_000, 125_000),
                    'unit_actual' => random_int(0, 30),
                ];
            }
        }
        TerritoryQuota::insert($quotas);

        // Create territory records
        $records = [];
        foreach ($territories as $territory) {
            foreach ($companies->random(min($companies->count(), 5)) as $company) {
                $records[] = [
                    'territory_id' => $territory->id,
                    'record_type' => \App\Models\Company::class,
                    'record_id' => $company->id,
                    'is_primary' => true,
                    'assigned_at' => now()->subDays(random_int(1, 90)),
                    'assignment_reason' => 'Strategic account',
                ];
            }

            foreach ($leads->random(min($leads->count(), 5)) as $lead) {
                $records[] = [
                    'territory_id' => $territory->id,
                    'record_type' => \App\Models\Lead::class,
                    'record_id' => $lead->id,
                    'is_primary' => false,
                    'assigned_at' => now()->subDays(random_int(1, 90)),
                    'assignment_reason' => 'Inbound lead',
                ];
            }
        }

        TerritoryRecord::insert($records);

        // Create overlaps
        $overlaps = [];
        foreach ($territories as $territory) {
            $overlappingTerritories = $territories->where('id', '!=', $territory->id)->shuffle()->take(1);
            foreach ($overlappingTerritories as $overlapping) {
                $overlaps[] = [
                    'territory_a_id' => $territory->id,
                    'territory_b_id' => $overlapping->id,
                    'resolution_strategy' => fake()->randomElement(['split', 'priority', 'manual']),
                    'priority_territory_id' => fake()->boolean() ? $territory->id : $overlapping->id,
                    'notes' => 'Seeded overlap',
                ];
            }
        }
        TerritoryOverlap::insert($overlaps);

        // Create transfers
        $transfers = [];
        foreach ($territories as $territory) {
            if ($territories->count() < 2) {
                break;
            }

            $transfers[] = [
                'from_territory_id' => $territory->id,
                'to_territory_id' => $territories->where('id', '!=', $territory->id)->first()->id,
                'record_type' => \App\Models\Company::class,
                'record_id' => $companies->first()->id,
                'initiated_by' => $users->random()->id,
                'reason' => 'Rebalance coverage',
            ];
        }
        TerritoryTransfer::insert($transfers);

        $this->command->info('✓ Created territories with assignments, quotas, records, overlaps, and transfers');
    }
}
