<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminder;
use App\Models\InvoiceStatusHistory;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating invoices (400) with line items, payments, and reminders...');

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

        // Create invoices one by one to ensure unique invoice numbers
        $invoiceIds = [];
        for ($i = 0; $i < 400; $i++) {
            $invoice = Invoice::factory()->create([
                'team_id' => $teams->random()->id,
                'company_id' => $companies->isNotEmpty() ? $companies->random()->id : null,
                'contact_id' => $people->isNotEmpty() ? $people->random()->id : null,
                'opportunity_id' => $opportunities->isNotEmpty() ? $opportunities->random()->id : null,
                'creator_id' => $users->random()->id,
            ]);
            $invoiceIds[] = $invoice->id;

            // Create line items
            $lineItems = [];
            for ($j = 0; $j < random_int(2, 8); $j++) {
                $quantity = random_int(1, 20);
                $unitPrice = fake()->randomFloat(2, 10, 5000);

                $lineItems[] = [
                    'invoice_id' => $invoice->id,
                    'team_id' => $invoice->team_id,
                    'product_id' => $products->isNotEmpty() ? $products->random()->id : null,
                    'description' => fake()->sentence(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            InvoiceLineItem::insert($lineItems);

            // Create payments for some invoices
            if (random_int(1, 100) > 40) {
                $payments = [];
                for ($j = 0; $j < random_int(1, 3); $j++) {
                    $payments[] = [
                        'invoice_id' => $invoice->id,
                        'team_id' => $invoice->team_id,
                        'amount' => fake()->randomFloat(2, 100, 10000),
                        'payment_date' => now()->subDays(random_int(0, 90)),
                        'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer', 'cash', 'check']),
                        'reference' => fake()->bothify('PAY-####-????'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                InvoicePayment::insert($payments);
            }

            // Sync financials
            $invoice->syncFinancials();
        }

        // Create reminders
        $reminders = [];
        foreach (array_rand(array_flip($invoiceIds), 500) as $invoiceId) {
            for ($i = 0; $i < random_int(1, 3); $i++) {
                $reminders[] = [
                    'invoice_id' => $invoiceId,
                    'team_id' => $teams->random()->id,
                    'sent_at' => now()->subDays(random_int(1, 60)),
                    'sent_by' => $users->random()->id,
                    'reminder_type' => fake()->randomElement(['first', 'second', 'final']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($reminders, 500) as $chunk) {
            InvoiceReminder::insert($chunk);
        }

        // Create status history
        $statusHistory = [];
        foreach (array_rand(array_flip($invoiceIds), 800) as $invoiceId) {
            for ($i = 0; $i < random_int(1, 4); $i++) {
                $statusHistory[] = [
                    'invoice_id' => $invoiceId,
                    'team_id' => $teams->random()->id,
                    'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
                    'changed_by' => $users->random()->id,
                    'notes' => fake()->sentence(),
                    'created_at' => now()->subDays(random_int(1, 90)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($statusHistory, 500) as $chunk) {
            InvoiceStatusHistory::insert($chunk);
        }

        $this->command->info('✓ Created 400 invoices with line items, payments, and reminders');
    }
}
