<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Knowledge\ArticleVisibility;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyRevenue;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\InvoicePayment;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleApproval;
use App\Models\KnowledgeArticleComment;
use App\Models\KnowledgeArticleRating;
use App\Models\KnowledgeArticleRelation;
use App\Models\KnowledgeArticleVersion;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeFaq;
use App\Models\KnowledgeTag;
use App\Models\KnowledgeTemplateResponse;
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

/**
 * Consolidated seeder that creates comprehensive test data for the entire application.
 *
 * This seeder combines all individual seeders into one comprehensive data generation process.
 * It creates realistic relationships between all entities and fills all fields properly.
 */
final class ConsolidatedSeeder extends Seeder
{
    private Team $team;

    private \Illuminate\Database\Eloquent\Collection $users;

    private \Illuminate\Database\Eloquent\Collection $companies;

    private \Illuminate\Database\Eloquent\Collection $people;

    private \Illuminate\Database\Eloquent\Collection $opportunities;

    private \Illuminate\Database\Eloquent\Collection $leads;

    private \Illuminate\Database\Eloquent\Collection $knowledgeCategories;

    private \Illuminate\Database\Eloquent\Collection $knowledgeTags;

    public function run(): void
    {
        $this->command?->info('🚀 Starting consolidated seeding...');

        $this->seedUsersAndTeam();
        $this->seedAccounts();
        $this->seedCompanies();
        $this->seedPeople();
        $this->seedLeads();
        $this->seedOpportunities();
        $this->seedTasks();
        $this->seedNotes();
        $this->seedInvoices();
        $this->seedSupportCases();
        $this->seedKnowledgeBase();
        $this->seedProcesses();

        $this->command?->info('✅ Consolidated seeding completed successfully!');
    }

    public function seedUsersAndTeam(): void
    {
        $this->command->info('👥 Creating users and team...');

        $owner = User::factory()
            ->withPersonalTeam()
            ->create([
                'name' => 'Demo Owner',
                'email' => 'owner@example.com',
            ]);

        $this->team = $owner->personalTeam();

        // Create team members
        $this->users = User::factory()
            ->count(20)
            ->create()
            ->each(function (User $user): void {
                $user->teams()->attach($this->team, ['role' => 'member']);
            });

        // Add owner to users collection
        $this->users->prepend($owner);

        $this->command->info("✓ Created {$this->users->count()} users");
    }

    public function seedAccounts(): void
    {
        $this->command?->info('🏢 Creating accounts...');

        Account::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'owner_id' => fn () => $this->users->random()->id,
                'assigned_to_id' => fn () => $this->users->random()->id,
            ]);

        $this->command?->info('✓ Created 100 accounts');
    }

    public function seedCompanies(): void
    {
        $this->command?->info('🏭 Creating companies...');

        $this->companies = Company::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'account_owner_id' => fn () => $this->users->random()->id,
            ])
            ->each(function (Company $company): void {
                // Create company revenues
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

        $this->command?->info('✓ Created 100 companies with revenues');
    }

    public function seedPeople(): void
    {
        $this->command?->info('👤 Creating people/contacts...');

        $this->people = People::factory()
            ->count(200)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => fn () => $this->companies->random()->id,
            ]);

        $this->command?->info('✓ Created 200 people');
    }

    public function seedLeads(): void
    {
        $this->command?->info('🎯 Creating leads...');

        // Pre-fetch IDs for faster random selection
        $companyIds = $this->companies->pluck('id')->all();
        $userIds = $this->users->pluck('id')->all();

        $leads = [];
        $now = now();

        for ($i = 0; $i < 150; $i++) {
            $leads[] = [
                'team_id' => $this->team->id,
                'creator_id' => $userIds[array_rand($userIds)],
                'company_id' => $companyIds[array_rand($companyIds)],
                'assigned_to_id' => $userIds[array_rand($userIds)],
                'name' => fake()->name(),
                'job_title' => fake()->jobTitle(),
                'company_name' => fake()->company(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->e164PhoneNumber(),
                'mobile' => fake()->e164PhoneNumber(),
                'website' => fake()->url(),
                'source' => fake()->randomElement(\App\Enums\LeadSource::cases())->value,
                'status' => fake()->randomElement(\App\Enums\LeadStatus::cases())->value,
                'score' => fake()->numberBetween(0, 100),
                'grade' => fake()->randomElement(\App\Enums\LeadGrade::cases())->value,
                'assignment_strategy' => fake()->randomElement(\App\Enums\LeadAssignmentStrategy::cases())->value,
                'nurture_status' => fake()->randomElement(\App\Enums\LeadNurtureStatus::cases())->value,
                'territory' => fake()->state(),
                'last_activity_at' => $now,
                'created_at' => $now->copy()->subMinutes($i),
                'updated_at' => $now->copy()->subMinutes($i),
            ];
        }

        // Bulk insert leads
        Lead::insert($leads);

        // Reload leads for use in other seeders
        $this->leads = Lead::where('team_id', $this->team->id)->get();

        $this->command?->info('✓ Created 150 leads');
    }

    public function seedOpportunities(): void
    {
        $this->command?->info('💼 Creating opportunities...');

        // Pre-fetch IDs for faster random selection
        $companyIds = $this->companies->pluck('id')->all();
        $peopleIds = $this->people->pluck('id')->all();
        $userIds = $this->users->pluck('id')->all();

        // Create opportunities with bulk insert
        $opportunities = [];
        $collaborators = [];
        $now = now();
        $opportunityId = (Opportunity::max('id') ?? 0) + 1;

        for ($i = 0; $i < 100; $i++) {
            $opportunities[] = [
                'name' => fake()->sentence(),
                'team_id' => $this->team->id,
                'company_id' => $companyIds[array_rand($companyIds)],
                'contact_id' => $peopleIds[array_rand($peopleIds)],
                'created_at' => $now->copy()->subMinutes($i),
                'updated_at' => $now->copy()->subMinutes($i),
            ];

            // Add collaborators
            $collaboratorCount = random_int(1, 3);
            $selectedCollaborators = array_rand(array_flip($userIds), $collaboratorCount);
            $selectedCollaborators = is_array($selectedCollaborators) ? $selectedCollaborators : [$selectedCollaborators];

            foreach ($selectedCollaborators as $userId) {
                $collaborators[] = [
                    'opportunity_id' => $opportunityId,
                    'user_id' => $userId,
                ];
            }

            $opportunityId++;
        }

        // Bulk insert opportunities
        Opportunity::insert($opportunities);

        // Bulk insert collaborators
        DB::table('opportunity_user')->insert($collaborators);

        // Reload opportunities for use in other seeders
        $this->opportunities = Opportunity::where('team_id', $this->team->id)->get();

        $this->command->info('✓ Created 100 opportunities with collaborators');
    }

    public function seedTasks(): void
    {
        $this->command?->info('✅ Creating tasks...');

        // Pre-fetch IDs for faster random selection
        $userIds = $this->users->pluck('id')->all();
        $companyIds = $this->companies->random(50)->pluck('id')->all();
        $peopleIds = $this->people->random(50)->pluck('id')->all();
        $opportunityIds = $this->opportunities->random(50)->pluck('id')->all();
        $leadIds = $this->leads->random(50)->pluck('id')->all();

        $tasks = [];
        $taskables = [];
        $taskAssignees = [];
        $now = now();
        $taskId = (Task::max('id') ?? 0) + 1;

        // Helper to create task data
        $createTaskData = function (int $index) use ($userIds, $now): array {
            return [
                'title' => fake()->sentence(3),
                'team_id' => $this->team->id,
                'creator_id' => $userIds[array_rand($userIds)],
                'created_at' => $now->copy()->subMinutes($index),
                'updated_at' => $now->copy()->subMinutes($index),
            ];
        };

        // Helper to add taskable and assignees
        $addTaskRelations = function (int $taskId, string $taskableType, int $taskableId) use (&$taskables, &$taskAssignees, $userIds, $now): void {
            $taskables[] = [
                'task_id' => $taskId,
                'taskable_type' => $taskableType,
                'taskable_id' => $taskableId,
            ];

            // Add 1-2 assignees
            $assigneeCount = random_int(1, 2);
            $selectedAssignees = array_rand(array_flip($userIds), $assigneeCount);
            $selectedAssignees = is_array($selectedAssignees) ? $selectedAssignees : [$selectedAssignees];

            foreach ($selectedAssignees as $userId) {
                $taskAssignees[] = [
                    'task_id' => $taskId,
                    'user_id' => $userId,
                ];
            }
        };

        $index = 0;

        // Tasks for companies
        foreach ($companyIds as $companyId) {
            $count = random_int(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $tasks[] = $createTaskData($index);
                $addTaskRelations($taskId, Company::class, $companyId);
                $taskId++;
                $index++;
            }
        }

        // Tasks for people
        foreach ($peopleIds as $personId) {
            $count = random_int(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $tasks[] = $createTaskData($index);
                $addTaskRelations($taskId, People::class, $personId);
                $taskId++;
                $index++;
            }
        }

        // Tasks for opportunities
        foreach ($opportunityIds as $opportunityId) {
            $count = random_int(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $tasks[] = $createTaskData($index);
                $addTaskRelations($taskId, Opportunity::class, $opportunityId);
                $taskId++;
                $index++;
            }
        }

        // Tasks for leads
        foreach ($leadIds as $leadId) {
            $count = random_int(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $tasks[] = $createTaskData($index);
                $addTaskRelations($taskId, Lead::class, $leadId);
                $taskId++;
                $index++;
            }
        }

        // Bulk insert tasks
        foreach (array_chunk($tasks, 500) as $chunk) {
            Task::insert($chunk);
        }

        // Bulk insert taskables
        foreach (array_chunk($taskables, 500) as $chunk) {
            DB::table('taskables')->insert($chunk);
        }

        // Bulk insert task assignees
        foreach (array_chunk($taskAssignees, 500) as $chunk) {
            DB::table('task_user')->insert($chunk);
        }

        $this->command?->info('✓ Created '.count($tasks).' tasks with assignees');
    }

    public function seedNotes(): void
    {
        $this->command?->info('📝 Creating notes...');

        // Pre-fetch IDs for faster random selection
        $userIds = $this->users->pluck('id')->all();
        $companyIds = $this->companies->random(50)->pluck('id')->all();
        $peopleIds = $this->people->random(50)->pluck('id')->all();
        $opportunityIds = $this->opportunities->random(50)->pluck('id')->all();
        $leadIds = $this->leads->random(50)->pluck('id')->all();

        $notes = [];
        $noteables = [];
        $now = now();
        $noteId = (Note::max('id') ?? 0) + 1;

        // Helper to create note data
        $createNoteData = function (int $index) use ($userIds, $now): array {
            return [
                'title' => fake()->sentence(),
                'team_id' => $this->team->id,
                'creator_id' => $userIds[array_rand($userIds)],
                'category' => \App\Enums\NoteCategory::GENERAL->value,
                'visibility' => \App\Enums\NoteVisibility::INTERNAL->value,
                'is_template' => false,
                'created_at' => $now->copy()->subMinutes($index),
                'updated_at' => $now->copy()->subMinutes($index),
            ];
        };

        $index = 0;

        // Notes for companies
        foreach ($companyIds as $companyId) {
            $count = random_int(2, 5);
            for ($i = 0; $i < $count; $i++) {
                $notes[] = $createNoteData($index);
                $noteables[] = [
                    'note_id' => $noteId,
                    'noteable_type' => Company::class,
                    'noteable_id' => $companyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $noteId++;
                $index++;
            }
        }

        // Notes for people
        foreach ($peopleIds as $personId) {
            $count = random_int(2, 5);
            for ($i = 0; $i < $count; $i++) {
                $notes[] = $createNoteData($index);
                $noteables[] = [
                    'note_id' => $noteId,
                    'noteable_type' => People::class,
                    'noteable_id' => $personId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $noteId++;
                $index++;
            }
        }

        // Notes for opportunities
        foreach ($opportunityIds as $opportunityId) {
            $count = random_int(2, 5);
            for ($i = 0; $i < $count; $i++) {
                $notes[] = $createNoteData($index);
                $noteables[] = [
                    'note_id' => $noteId,
                    'noteable_type' => Opportunity::class,
                    'noteable_id' => $opportunityId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $noteId++;
                $index++;
            }
        }

        // Notes for leads
        foreach ($leadIds as $leadId) {
            $count = random_int(2, 5);
            for ($i = 0; $i < $count; $i++) {
                $notes[] = $createNoteData($index);
                $noteables[] = [
                    'note_id' => $noteId,
                    'noteable_type' => Lead::class,
                    'noteable_id' => $leadId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $noteId++;
                $index++;
            }
        }

        // Bulk insert notes
        foreach (array_chunk($notes, 500) as $chunk) {
            Note::insert($chunk);
        }

        // Bulk insert noteables
        foreach (array_chunk($noteables, 500) as $chunk) {
            DB::table('noteables')->insert($chunk);
        }

        $this->command?->info('✓ Created '.count($notes).' notes');
    }

    public function seedInvoices(): void
    {
        $this->command?->info('💰 Creating invoices...');

        // Pre-fetch IDs for faster random selection
        $companyIds = $this->companies->pluck('id')->all();
        $peopleIds = $this->people->pluck('id')->all();
        $opportunityIds = $this->opportunities->pluck('id')->all();
        $userIds = $this->users->pluck('id')->all();

        $invoices = [];
        $lineItems = [];
        $payments = [];
        $now = now();
        $invoiceId = (Invoice::max('id') ?? 0) + 1;
        $lineItemId = (InvoiceLineItem::max('id') ?? 0) + 1;

        for ($i = 0; $i < 50; $i++) {
            $issueDate = $now->copy()->subDays(random_int(0, 90));
            $invoiceNumber = 'INV-'.str_pad((string) ($invoiceId + $i), 6, '0', STR_PAD_LEFT);

            $invoices[] = [
                'team_id' => $this->team->id,
                'company_id' => $companyIds[array_rand($companyIds)],
                'contact_id' => $peopleIds[array_rand($peopleIds)],
                'opportunity_id' => $opportunityIds[array_rand($opportunityIds)],
                'creator_id' => $userIds[array_rand($userIds)],
                'invoice_number' => $invoiceNumber,
                'issue_date' => $issueDate,
                'due_date' => $issueDate->copy()->addDays(30),
                'payment_terms' => 'Net 30',
                'currency_code' => config('company.default_currency', 'USD'),
                'status' => fake()->randomElement(\App\Enums\InvoiceStatus::cases())->value,
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'late_fee_rate' => 0,
                'total' => 0,
                'balance_due' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Create line items for this invoice
            $lineItemCount = random_int(2, 8);
            for ($j = 0; $j < $lineItemCount; $j++) {
                $lineItems[] = [
                    'invoice_id' => $invoiceId,
                    'team_id' => $this->team->id,
                    'name' => fake()->sentence(3),
                    'description' => fake()->sentence(),
                    'quantity' => fake()->numberBetween(1, 5),
                    'unit_price' => fake()->randomFloat(2, 50, 500),
                    'tax_rate' => fake()->randomElement([0, 5, 8, 10]),
                    'sort_order' => $j + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $lineItemId++;
            }

            // Create payments for some invoices (70% chance)
            if (random_int(1, 100) > 30) {
                $paymentCount = random_int(1, 3);
                for ($k = 0; $k < $paymentCount; $k++) {
                    $payments[] = [
                        'invoice_id' => $invoiceId,
                        'team_id' => $this->team->id,
                        'amount' => fake()->randomFloat(2, 100, 500),
                        'currency_code' => config('company.default_currency', 'USD'),
                        'paid_at' => $now->copy()->subDays(random_int(0, 30)),
                        'method' => fake()->randomElement(['card', 'bank_transfer', 'cash']),
                        'reference' => fake()->uuid(),
                        'status' => \App\Enums\InvoicePaymentStatus::COMPLETED->value,
                        'notes' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $invoiceId++;
        }

        // Bulk insert invoices
        Invoice::insert($invoices);

        // Bulk insert line items
        foreach (array_chunk($lineItems, 500) as $chunk) {
            InvoiceLineItem::insert($chunk);
        }

        // Bulk insert payments
        if (! empty($payments)) {
            foreach (array_chunk($payments, 500) as $chunk) {
                InvoicePayment::insert($chunk);
            }
        }

        // Sync financials for all invoices (this needs to be done after insert)
        Invoice::where('team_id', $this->team->id)
            ->whereIn('invoice_number', collect($invoices)->pluck('invoice_number'))
            ->each(fn (Invoice $invoice) => $invoice->syncFinancials());

        $this->command?->info('✓ Created 50 invoices with line items and payments');
    }

    public function seedSupportCases(): void
    {
        $this->command?->info('🎫 Creating support cases...');

        // Pre-fetch IDs for faster random selection
        $companyIds = $this->companies->pluck('id')->all();
        $peopleIds = $this->people->pluck('id')->all();
        $userIds = $this->users->pluck('id')->all();

        // Build all support cases data in memory first
        $supportCases = [];
        $now = now();

        for ($i = 0; $i < 50; $i++) {
            $supportCases[] = [
                'case_number' => 'CASE-'.strtoupper(\Illuminate\Support\Str::random(8)),
                'subject' => fake()->sentence(),
                'description' => fake()->paragraph(),
                'status' => fake()->randomElement(\App\Enums\CaseStatus::cases())->value,
                'priority' => fake()->randomElement(\App\Enums\CasePriority::cases())->value,
                'type' => fake()->randomElement(\App\Enums\CaseType::cases())->value,
                'channel' => fake()->randomElement(\App\Enums\CaseChannel::cases())->value,
                'queue' => fake()->randomElement(['general', 'billing', 'technical', 'product']),
                'sla_due_at' => fake()->dateTimeBetween('+1 day', '+5 days'),
                'team_id' => $this->team->id,
                'creator_id' => $userIds[array_rand($userIds)],
                'company_id' => $companyIds[array_rand($companyIds)],
                'contact_id' => $peopleIds[array_rand($peopleIds)],
                'assigned_to_id' => $userIds[array_rand($userIds)],
                'assigned_team_id' => $this->team->id,
                'created_at' => $now->copy()->subMinutes($i),
                'updated_at' => $now->copy()->subMinutes($i),
            ];
        }

        // Bulk insert for maximum performance
        SupportCase::insert($supportCases);

        $this->command?->info('✓ Created 50 support cases');
    }

    public function seedKnowledgeBase(): void
    {
        $this->command?->info('📚 Creating knowledge base...');

        // Create categories
        $this->knowledgeCategories = KnowledgeCategory::factory()
            ->count(30)
            ->create([
                'team_id' => $this->team->id,
                'creator_id' => fn () => $this->users->random()->id,
            ]);

        // Create tags
        $this->knowledgeTags = KnowledgeTag::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'creator_id' => fn () => $this->users->random()->id,
            ]);

        // Create articles
        $articles = KnowledgeArticle::factory()
            ->count(500)
            ->create([
                'team_id' => $this->team->id,
                'category_id' => fn () => $this->knowledgeCategories->random()->id,
                'author_id' => fn () => $this->users->random()->id,
                'creator_id' => fn () => $this->users->random()->id,
            ]);

        // Attach tags to articles
        $articleTags = [];
        foreach ($articles as $article) {
            $selectedTags = $this->knowledgeTags->random(random_int(1, 5));
            foreach ($selectedTags as $tag) {
                $articleTags[] = [
                    'article_id' => $article->id,
                    'tag_id' => $tag->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('knowledge_article_tag')->insert($articleTags);

        // Create article versions
        $versions = [];
        foreach ($articles->random(300) as $article) {
            for ($i = 0; $i < random_int(1, 5); $i++) {
                $versions[] = [
                    'article_id' => $article->id,
                    'team_id' => $article->team_id,
                    'editor_id' => $this->users->random()->id,
                    'version' => $i + 1,
                    'title' => $article->title . ' v' . ($i + 1),
                    'slug' => $article->slug . '-v' . ($i + 1),
                    'content' => fake()->paragraphs(5, true),
                    'status' => $article->status->value,
                    'visibility' => $article->visibility->value,
                    'created_at' => now()->subDays(random_int(1, 365)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($versions, 500) as $chunk) {
            KnowledgeArticleVersion::insert($chunk);
        }

        // Create approvals
        $approvals = [];
        foreach ($articles->random(200) as $article) {
            $approvals[] = [
                'article_id' => $article->id,
                'team_id' => $article->team_id,
                'requested_by_id' => $this->users->random()->id,
                'approver_id' => $this->users->random()->id,
                'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                'decided_at' => fake()->boolean(70) ? now()->subDays(random_int(1, 90)) : null,
                'decision_notes' => fake()->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        KnowledgeArticleApproval::insert($approvals);

        // Create comments
        $comments = [];
        foreach ($articles->random(300) as $article) {
            for ($i = 0; $i < random_int(1, 10); $i++) {
                $comments[] = [
                    'article_id' => $article->id,
                    'team_id' => $article->team_id,
                    'author_id' => $this->users->random()->id,
                    'body' => fake()->paragraph(),
                    'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                    'is_internal' => fake()->boolean(30),
                    'created_at' => now()->subDays(random_int(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($comments, 500) as $chunk) {
            KnowledgeArticleComment::insert($chunk);
        }

        // Create ratings (ensure unique article_id + user_id combinations)
        $ratings = [];
        $usedCombinations = [];

        foreach ($articles->random(400) as $article) {
            $availableUsers = $this->users->shuffle();
            $ratingsCount = min(random_int(1, 10), $availableUsers->count());

            for ($i = 0; $i < $ratingsCount; $i++) {
                $userId = $availableUsers[$i]->id;
                $combination = "{$article->id}-{$userId}";

                if (! isset($usedCombinations[$combination])) {
                    $usedCombinations[$combination] = true;
                    $ratings[] = [
                        'article_id' => $article->id,
                        'team_id' => $article->team_id,
                        'user_id' => $userId,
                        'rating' => random_int(1, 5),
                        'feedback' => fake()->boolean(50) ? fake()->sentence() : null,
                        'context' => fake()->randomElement(['web', 'mobile', 'api']),
                        'ip_address' => fake()->ipv4(),
                        'created_at' => now()->subDays(random_int(1, 180)),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($ratings, 500) as $chunk) {
            KnowledgeArticleRating::insert($chunk);
        }

        // Create article relations
        $relations = [];
        foreach ($articles->random(200) as $article) {
            $relatedArticles = $articles->where('id', '!=', $article->id)->random(random_int(1, 3));
            foreach ($relatedArticles as $related) {
                $relations[] = [
                    'team_id' => $article->team_id,
                    'article_id' => $article->id,
                    'related_article_id' => $related->id,
                    'relation_type' => fake()->randomElement(['related', 'prerequisite', 'follow_up']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        KnowledgeArticleRelation::insert($relations);

        // Create FAQs
        KnowledgeFaq::factory()
            ->count(200)
            ->create([
                'team_id' => $this->team->id,
                'creator_id' => fn () => $this->users->random()->id,
                'article_id' => fn () => $articles->random()->id,
            ]);

        // Create template responses with proper fields
        $this->seedKnowledgeTemplateResponses();

        $this->command?->info('✓ Created knowledge base with 500 articles, 200 FAQs, 100 templates');
    }

    private function seedKnowledgeTemplateResponses(): void
    {
        $templates = [];

        $templateData = [
            // Support templates
            [
                'title' => 'Welcome New Customer',
                'body' => "Dear [Customer Name],\n\nWelcome to our platform! We're excited to have you on board.\n\nTo get started:\n1. Complete your profile\n2. Explore our features\n3. Contact support if you need help\n\nBest regards,\nSupport Team",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Password Reset Instructions',
                'body' => "Hello [Customer Name],\n\nWe received a request to reset your password.\n\nTo reset your password:\n1. Click the link below\n2. Enter your new password\n3. Confirm the change\n\nIf you didn't request this, please ignore this message.\n\nBest regards,\nSecurity Team",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Technical Issue Acknowledgment',
                'body' => "Hi [Customer Name],\n\nThank you for reporting the technical issue. We've received your ticket #[TICKET_NUMBER].\n\nOur team is investigating and will update you within [TIMEFRAME].\n\nIssue details:\n- Priority: [PRIORITY]\n- Category: [CATEGORY]\n- Assigned to: [AGENT]\n\nThank you for your patience.\n\nBest regards,\nTechnical Support",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Feature Request Received',
                'body' => "Hello [Customer Name],\n\nThank you for your feature request!\n\nWe've added it to our product roadmap for review. Our product team evaluates all requests based on:\n- User demand\n- Technical feasibility\n- Strategic alignment\n\nWe'll notify you of any updates.\n\nBest regards,\nProduct Team",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Account Suspension Notice',
                'body' => "Dear [Customer Name],\n\nYour account has been temporarily suspended due to [REASON].\n\nTo reactivate your account:\n1. Review our terms of service\n2. Contact support at [EMAIL]\n3. Provide necessary documentation\n\nAccount ID: [ACCOUNT_ID]\nSuspension Date: [DATE]\n\nBest regards,\nCompliance Team",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            // Sales templates
            [
                'title' => 'Initial Sales Outreach',
                'body' => "Hi [Prospect Name],\n\nI noticed [COMPANY] is [OBSERVATION]. I wanted to reach out because we help companies like yours [VALUE PROPOSITION].\n\nWould you be open to a brief call to discuss how we can help?\n\nBest regards,\n[Your Name]\n[Title]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Follow-up After Demo',
                'body' => "Hi [Prospect Name],\n\nThank you for taking the time to see our demo yesterday.\n\nAs discussed, here are the key benefits for [COMPANY]:\n- [BENEFIT 1]\n- [BENEFIT 2]\n- [BENEFIT 3]\n\nNext steps:\n1. [ACTION 1]\n2. [ACTION 2]\n\nLet me know if you have any questions!\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Proposal Submission',
                'body' => "Dear [Prospect Name],\n\nPlease find attached our proposal for [PROJECT/SERVICE].\n\nProposal highlights:\n- Investment: [AMOUNT]\n- Timeline: [DURATION]\n- Deliverables: [LIST]\n- ROI: [EXPECTED_ROI]\n\nI'm available to discuss any questions you may have.\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Contract Negotiation',
                'body' => "Hi [Prospect Name],\n\nThank you for reviewing our proposal. I understand you have some questions about [TOPIC].\n\nLet's address your concerns:\n1. [CONCERN 1]: [RESPONSE]\n2. [CONCERN 2]: [RESPONSE]\n\nI'm confident we can find a solution that works for both parties.\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Deal Closing',
                'body' => "Dear [Customer Name],\n\nCongratulations on your decision to partner with us!\n\nNext steps:\n1. Sign the contract (attached)\n2. Complete onboarding form\n3. Schedule kickoff meeting\n\nYour dedicated account manager will be [NAME].\n\nWelcome aboard!\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            // General templates
            [
                'title' => 'Meeting Confirmation',
                'body' => "Hi [Name],\n\nThis confirms our meeting:\n\nDate: [DATE]\nTime: [TIME]\nDuration: [DURATION]\nLocation: [LOCATION/LINK]\n\nAgenda:\n1. [TOPIC 1]\n2. [TOPIC 2]\n3. [TOPIC 3]\n\nLooking forward to speaking with you!\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Meeting Reschedule Request',
                'body' => "Hi [Name],\n\nI need to reschedule our meeting originally planned for [ORIGINAL_DATE].\n\nWould any of these times work for you?\n- [OPTION 1]\n- [OPTION 2]\n- [OPTION 3]\n\nApologies for any inconvenience.\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Thank You Note',
                'body' => "Dear [Name],\n\nThank you for [ACTION/MEETING/PURCHASE].\n\n[PERSONALIZED_MESSAGE]\n\nIf you need anything, please don't hesitate to reach out.\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Out of Office Reply',
                'body' => "Thank you for your email.\n\nI'm currently out of the office from [START_DATE] to [END_DATE] with limited access to email.\n\nFor urgent matters, please contact:\n- [BACKUP_NAME]: [EMAIL]\n- [BACKUP_NAME_2]: [EMAIL]\n\nI'll respond to your message when I return.\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
            [
                'title' => 'Feedback Request',
                'body' => "Hi [Customer Name],\n\nWe'd love to hear about your experience with [PRODUCT/SERVICE].\n\nCould you take 2 minutes to share your feedback?\n[SURVEY_LINK]\n\nYour input helps us improve and serve you better.\n\nThank you!\n\nBest regards,\n[Your Name]",
                'visibility' => ArticleVisibility::INTERNAL,
            ],
        ];

        foreach ($templateData as $data) {
            $templates[] = [
                'team_id' => $this->team->id,
                'category_id' => $this->knowledgeCategories->random()->id,
                'creator_id' => $this->users->random()->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'visibility' => $data['visibility']->value,
                'is_active' => fake()->boolean(90),
                'created_at' => now()->subDays(random_int(1, 180)),
                'updated_at' => now(),
            ];
        }

        // Add more random templates
        for ($i = 0; $i < 85; $i++) {
            $templates[] = [
                'team_id' => $this->team->id,
                'category_id' => $this->knowledgeCategories->random()->id,
                'creator_id' => $this->users->random()->id,
                'title' => fake()->sentence(4),
                'body' => fake()->paragraphs(3, true),
                'visibility' => fake()->randomElement(ArticleVisibility::cases())->value,
                'is_active' => fake()->boolean(85),
                'created_at' => now()->subDays(random_int(1, 365)),
                'updated_at' => now(),
            ];
        }

        KnowledgeTemplateResponse::insert($templates);
    }

    public function seedProcesses(): void
    {
        $this->command?->info('⚙️ Creating processes...');

        ProcessDefinition::factory()
            ->count(20)
            ->create([
                'team_id' => $this->team->id,
                'creator_id' => fn () => $this->users->random()->id,
            ])
            ->each(function (ProcessDefinition $definition): void {
                // Create executions for each process
                ProcessExecution::factory()
                    ->count(random_int(3, 10))
                    ->create([
                        'process_definition_id' => $definition->id,
                        'team_id' => $this->team->id,
                        'initiated_by' => $this->users->random()->id,
                    ]);
            });

        $this->command?->info('✓ Created 20 process definitions with executions');
    }
}
