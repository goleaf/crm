<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteLineItem;
use App\Models\QuoteStatusHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating quotes (200) with line items and status history...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $people = People::all();
        $opportunities = Opportunity::all();
        $products = Product::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $quotes = [];
        for ($i = 0; $i < 200; $i++) {
            $quotes[] = [
                'team_id' => $teams->random()->id,
                'company_id' => $companies->isNotEmpty() ? $companies->random()->id : null,
                'contact_id' => $people->isNotEmpty() ? $people->random()->id : null,
                'opportunity_id' => $opportunities->isNotEmpty() ? $opportunities->random()->id : null,
                'creator_id' => $users->random()->id,
                'title' => fake()->sentence(4),
                'status' => fake()->randomElement(['draft', 'sent', 'accepted', 'rejected']),
                'valid_until' => now()->addDays(random_int(7, 90)),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($quotes, 500) as $chunk) {
            Quote::insert($chunk);
        }

        $quoteIds = Quote::pluck('id');

        // Create line items
        $lineItems = [];
        foreach ($quoteIds as $quoteId) {
            for ($i = 0; $i < random_int(2, 8); $i++) {
                $quantity = random_int(1, 20);
                $unitPrice = fake()->randomFloat(2, 10, 5000);
                $taxRate = fake()->randomFloat(2, 0, 15);
                $lineTotal = $quantity * $unitPrice;
                $taxTotal = $lineTotal * ($taxRate / 100);

                $lineItems[] = [
                    'quote_id' => $quoteId,
                    'team_id' => $teams->random()->id,
                    'product_id' => $products->isNotEmpty() ? $products->random()->id : null,
                    'name' => fake()->words(3, true),
                    'description' => fake()->sentence(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'line_total' => $lineTotal,
                    'tax_total' => $taxTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($lineItems, 1000) as $chunk) {
            QuoteLineItem::insert($chunk);
        }

        // Create status history
        $statusHistory = [];
        $statuses = ['draft', 'sent', 'accepted', 'rejected'];
        foreach ($quoteIds->random(min(100, $quoteIds->count())) as $quoteId) {
            for ($i = 0; $i < random_int(1, 4); $i++) {
                $fromStatus = $i > 0 ? fake()->randomElement($statuses) : null;
                $toStatus = fake()->randomElement($statuses);

                $statusHistory[] = [
                    'quote_id' => $quoteId,
                    'team_id' => $teams->random()->id,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'changed_by' => $users->random()->id,
                    'note' => fake()->sentence(),
                    'created_at' => now()->subDays(random_int(1, 90)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($statusHistory, 500) as $chunk) {
            QuoteStatusHistory::insert($chunk);
        }

        $this->command->info('✓ Created 200 quotes with line items and status history');
    }
}
