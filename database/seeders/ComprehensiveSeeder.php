<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyRevenue;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\InvoicePayment;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeFaq;
use App\Models\KnowledgeTag;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\ProcessDefinition;
use App\Models\ProcessExecution;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ComprehensiveSeeder extends Seeder
{
    private Team $team;

    private User $owner;

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->command->info('Creating users and team...');
            $this->createUsersAndTeam();

            $this->command->info('Creating accounts (100)...');
            $accounts = $this->createAccounts();

            $this->command->info('Creating companies (100)...');
            $companies = $this->createCompanies();

            $this->command->info('Creating people/contacts (100)...');
            $people = $this->createPeople($companies);

            $this->command->info('Creating leads (100)...');
            $leads = $this->createLeads($companies);

            $this->command->info('Creating opportunities (100)...');
            $opportunities = $this->createOpportunities($companies, $people);

            $this->command->info('Creating tasks...');
            $this->createTasks($companies, $people, $opportunities, $leads);

            $this->command->info('Creating notes...');
            $this->createNotes($companies, $people, $opportunities, $leads);

            $this->command->info('Creating invoices (50)...');
            $this->createInvoices($companies, $people, $opportunities);

            $this->command->info('Creating support cases (50)...');
            $this->createSupportCases($companies, $people);

            $this->command->info('Creating knowledge base...');
            $this->createKnowledgeBase();

            $this->command->info('Creating processes...');
            $this->createProcesses();

            $this->command->info('Creating documents & shares...');
            $this->call(DocumentSeeder::class);

            $this->command->info('Seeding completed successfully!');
        });
    }

    private function createUsersAndTeam(): void
    {
        $this->owner = User::factory()
            ->withPersonalTeam()
            ->create([
                'name' => 'Demo Owner',
                'email' => 'owner@example.com',
            ]);

        $this->team = $this->owner->personalTeam();

        // Create 20 team members
        User::factory()
            ->count(20)
            ->create()
            ->each(function (User $user): void {
                $user->teams()->attach($this->team, ['role' => 'member']);
            });
    }

    private function createAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        $users = $this->team->allUsers();

        return Account::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'owner_id' => $users->random()->id,
                'assigned_to_id' => $users->random()->id,
            ]);
    }

    private function createCompanies(): \Illuminate\Database\Eloquent\Collection
    {
        $users = $this->team->allUsers();

        return Company::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'account_owner_id' => $users->random()->id,
            ])
            ->each(function (Company $company): void {
                // Create company revenues with unique years
                $years = range(2018, 2025);
                shuffle($years);
                $revenueCount = random_int(3, 8);

                for ($i = 0; $i < $revenueCount; $i++) {
                    CompanyRevenue::factory()->create([
                        'company_id' => $company->id,
                        'team_id' => $this->team->id,
                        'year' => $years[$i],
                    ]);
                }
            });
    }

    private function createPeople(\Illuminate\Database\Eloquent\Collection $companies): \Illuminate\Database\Eloquent\Collection
    {
        return People::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => $companies->random()->id,
            ]);
    }

    private function createLeads(\Illuminate\Database\Eloquent\Collection $companies): \Illuminate\Database\Eloquent\Collection
    {
        $users = $this->team->allUsers();

        return Lead::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => $companies->random()->id,
                'assigned_to_id' => $users->random()->id,
                'creator_id' => $users->random()->id,
            ]);
    }

    private function createOpportunities(
        \Illuminate\Database\Eloquent\Collection $companies,
        \Illuminate\Database\Eloquent\Collection $people
    ): \Illuminate\Database\Eloquent\Collection {
        $users = $this->team->allUsers();

        return Opportunity::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => $companies->random()->id,
                'contact_id' => $people->random()->id,
            ])
            ->each(function (Opportunity $opportunity) use ($users): void {
                // Attach team members as collaborators to opportunities
                $collaboratorCount = random_int(1, 3);
                $collaboratorIds = $users->pluck('id')->shuffle()->take($collaboratorCount);
                $opportunity->collaborators()->attach($collaboratorIds->toArray());
            });
    }

    private function createTasks(
        \Illuminate\Database\Eloquent\Collection $companies,
        \Illuminate\Database\Eloquent\Collection $people,
        \Illuminate\Database\Eloquent\Collection $opportunities,
        \Illuminate\Database\Eloquent\Collection $leads
    ): void {
        $users = $this->team->allUsers();

        // Tasks for companies
        $companies->random(50)->each(function (Company $company) use ($users): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $company->tasks()->attach($task->id);
                $task->assignees()->attach($users->random(random_int(1, 2))->pluck('id'));
            }
        });

        // Tasks for people
        $people->random(50)->each(function (People $person) use ($users): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $person->tasks()->attach($task->id);
                $task->assignees()->attach($users->random(random_int(1, 2))->pluck('id'));
            }
        });

        // Tasks for opportunities
        $opportunities->random(50)->each(function (Opportunity $opportunity) use ($users): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $opportunity->tasks()->attach($task->id);
                $task->assignees()->attach($users->random(random_int(1, 2))->pluck('id'));
            }
        });

        // Tasks for leads
        $leads->random(50)->each(function (Lead $lead) use ($users): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $lead->tasks()->attach($task->id);
                $task->assignees()->attach($users->random(random_int(1, 2))->pluck('id'));
            }
        });
    }

    private function createNotes(
        \Illuminate\Database\Eloquent\Collection $companies,
        \Illuminate\Database\Eloquent\Collection $people,
        \Illuminate\Database\Eloquent\Collection $opportunities,
        \Illuminate\Database\Eloquent\Collection $leads
    ): void {
        $users = $this->team->allUsers();

        // Notes for companies
        $companies->random(50)->each(function (Company $company) use ($users): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($notes as $note) {
                $company->notes()->attach($note->id);
            }
        });

        // Notes for people
        $people->random(50)->each(function (People $person) use ($users): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($notes as $note) {
                $person->notes()->attach($note->id);
            }
        });

        // Notes for opportunities
        $opportunities->random(50)->each(function (Opportunity $opportunity) use ($users): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($notes as $note) {
                $opportunity->notes()->attach($note->id);
            }
        });

        // Notes for leads
        $leads->random(50)->each(function (Lead $lead) use ($users): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $users->random()->id,
                ]);

            foreach ($notes as $note) {
                $lead->notes()->attach($note->id);
            }
        });
    }

    private function createInvoices(
        \Illuminate\Database\Eloquent\Collection $companies,
        \Illuminate\Database\Eloquent\Collection $people,
        \Illuminate\Database\Eloquent\Collection $opportunities
    ): void {
        $users = $this->team->allUsers();

        // Create invoices one at a time to avoid duplicate invoice numbers
        for ($i = 0; $i < 50; $i++) {
            $invoice = Invoice::factory()->create([
                'team_id' => $this->team->id,
                'company_id' => $companies->random()->id,
                'contact_id' => $people->random()->id,
                'opportunity_id' => $opportunities->random()->id,
                'creator_id' => $users->random()->id,
            ]);

            // Create line items
            InvoiceLineItem::factory()
                ->count(random_int(2, 8))
                ->create([
                    'invoice_id' => $invoice->id,
                    'team_id' => $this->team->id,
                ]);

            // Create payments for some invoices
            if (random_int(1, 100) > 30) {
                InvoicePayment::factory()
                    ->count(random_int(1, 3))
                    ->create([
                        'invoice_id' => $invoice->id,
                        'team_id' => $this->team->id,
                    ]);
            }

            // Sync financials
            $invoice->syncFinancials();
        }
    }

    private function createSupportCases(
        \Illuminate\Database\Eloquent\Collection $companies,
        \Illuminate\Database\Eloquent\Collection $people
    ): void {
        $users = $this->team->allUsers();

        SupportCase::factory()
            ->count(50)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => $companies->random()->id,
                'contact_id' => $people->random()->id,
                'assigned_to_id' => $users->random()->id,
            ]);
    }

    private function createKnowledgeBase(): void
    {
        $users = $this->team->allUsers();

        // Create categories
        $categories = KnowledgeCategory::factory()
            ->count(10)
            ->create([
                'team_id' => $this->team->id,
                'creator_id' => $users->random()->id,
            ]);

        // Create tags
        $tags = KnowledgeTag::factory()
            ->count(20)
            ->create([
                'team_id' => $this->team->id,
            ]);

        // Create articles
        KnowledgeArticle::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'category_id' => $categories->random()->id,
                'author_id' => $users->random()->id,
            ])
            ->each(function (KnowledgeArticle $article) use ($tags): void {
                // Attach tags
                $article->tags()->attach(
                    $tags->random(random_int(1, 5))->pluck('id')->toArray()
                );
            });

        // Create FAQs
        KnowledgeFaq::factory()
            ->count(50)
            ->create([
                'team_id' => $this->team->id,
                'creator_id' => $users->random()->id,
            ]);
    }

    private function createProcesses(): void
    {
        $users = $this->team->allUsers();

        ProcessDefinition::factory()
            ->count(20)
            ->create([
                'team_id' => $this->team->id,
                'creator_id' => $users->random()->id,
            ])
            ->each(function (ProcessDefinition $definition) use ($users): void {
                // Create executions for each process
                ProcessExecution::factory()
                    ->count(random_int(3, 10))
                    ->create([
                        'process_definition_id' => $definition->id,
                        'team_id' => $this->team->id,
                        'initiated_by' => $users->random()->id,
                    ]);
            });
    }
}
