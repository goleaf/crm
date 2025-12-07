<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Team;
use Illuminate\Database\Seeder;

final class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating customers (800)...');

        $teams = Team::all();
        $companies = Company::all();

        if ($teams->isEmpty()) {
            $this->command->warn('No teams found.');

            return;
        }

        $customers = [];
        for ($i = 0; $i < 800; $i++) {
            $customers[] = [
                'team_id' => $teams->random()->id,
                'company_id' => $companies->isNotEmpty() ? $companies->random()->id : null,
                'customer_number' => 'CUST-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'status' => fake()->randomElement(['active', 'inactive', 'suspended']),
                'tier' => fake()->randomElement(['bronze', 'silver', 'gold', 'platinum']),
                'lifetime_value' => fake()->randomFloat(2, 1000, 500000),
                'first_purchase_date' => now()->subDays(random_int(30, 1095)),
                'last_purchase_date' => now()->subDays(random_int(0, 365)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($customers, 500) as $chunk) {
            Customer::insert($chunk);
        }

        $this->command->info('✓ Created 800 customers');
    }
}
