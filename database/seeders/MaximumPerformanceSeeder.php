<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class MaximumPerformanceSeeder extends Seeder
{
    /**
     * Seed the application's database with maximum data for all models.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->command->info('🚀 Starting Maximum Performance Seeding...');

            // Core entities first
            $this->call([
                UserTeamSeeder::class,
                AccountSeeder::class,
                CompanySeeder::class,
                ContactSeeder::class,
                LeadSeeder::class,
                OpportunitySeeder::class,
                ProductSeeder::class,
                // VendorSeeder::class, // Skipped - vendors table doesn't exist
                // CustomerSeeder::class, // Skipped - customers is a view, not a table
                // MembershipSeeder::class, // Skipped - need to verify table exists
            ]);

            // Sales & Orders
            $this->call([
                QuoteSeeder::class,
                OrderSeeder::class,
                InvoiceSeeder::class,
                // PurchaseOrderSeeder::class, // Skipped - depends on vendors table
                // DeliverySeeder::class, // Skipped - depends on orders/shipments
            ]);

            // Support & Knowledge
            $this->call([
                SupportCaseSeeder::class,
                KnowledgeBaseSeeder::class,
            ]);

            // Tasks & Activities
            $this->call([
                TaskSeeder::class,
                NoteSeeder::class,
                ActivitySeeder::class,
            ]);

            // Advanced Features
            $this->call([
                ProcessManagementSeeder::class,
                ExtensionSeeder::class,
                PdfTemplateSeeder::class,
                TerritorySeeder::class,
                EmailProgramSeeder::class,
            ]);

            // Additional entities
            $this->call([
                TagSeeder::class,
                ImportSeeder::class,
                AiSummarySeeder::class,
            ]);

            $this->command->info('✅ Maximum Performance Seeding Completed!');
        });
    }
}
