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

        $this->leads = Lead::factory()
            ->count(150)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => fn () => $this->companies->random()->id,
                'assigned_to_id' => fn () => $this->users->random()->id,
                'creator_id' => fn () => $this->users->random()->id,
            ]);

        $this->command?->info('✓ Created 150 leads');
    }

    public function seedOpportunities(): void
    {
        $this->command?->info('💼 Creating opportunities...');

        $this->opportunities = Opportunity::factory()
            ->count(100)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => fn () => $this->companies->random()->id,
                'contact_id' => fn () => $this->people->random()->id,
            ])
            ->each(function (Opportunity $opportunity): void {
                // Attach collaborators
                $collaboratorCount = random_int(1, 3);
                $collaboratorIds = $this->users->pluck('id')->shuffle()->take($collaboratorCount);
                $opportunity->collaborators()->attach($collaboratorIds->toArray());
            });

        $this->command->info('✓ Created 100 opportunities with collaborators');
    }

    public function seedTasks(): void
    {
        $this->command?->info('✅ Creating tasks...');

        $taskCount = 0;

        // Tasks for companies
        $this->companies->random(50)->each(function (Company $company) use (&$taskCount): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $company->tasks()->attach($task->id);
                $task->assignees()->attach($this->users->random(random_int(1, 2))->pluck('id'));
                $taskCount++;
            }
        });

        // Tasks for people
        $this->people->random(50)->each(function (People $person) use (&$taskCount): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $person->tasks()->attach($task->id);
                $task->assignees()->attach($this->users->random(random_int(1, 2))->pluck('id'));
                $taskCount++;
            }
        });

        // Tasks for opportunities
        $this->opportunities->random(50)->each(function (Opportunity $opportunity) use (&$taskCount): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $opportunity->tasks()->attach($task->id);
                $task->assignees()->attach($this->users->random(random_int(1, 2))->pluck('id'));
                $taskCount++;
            }
        });

        // Tasks for leads
        $this->leads->random(50)->each(function (Lead $lead) use (&$taskCount): void {
            $tasks = Task::factory()
                ->count(random_int(1, 3))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($tasks as $task) {
                $lead->tasks()->attach($task->id);
                $task->assignees()->attach($this->users->random(random_int(1, 2))->pluck('id'));
                $taskCount++;
            }
        });

        $this->command?->info("✓ Created {$taskCount} tasks with assignees");
    }

    public function seedNotes(): void
    {
        $this->command?->info('📝 Creating notes...');

        $noteCount = 0;

        // Notes for companies
        $this->companies->random(50)->each(function (Company $company) use (&$noteCount): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($notes as $note) {
                $company->notes()->attach($note->id);
                $noteCount++;
            }
        });

        // Notes for people
        $this->people->random(50)->each(function (People $person) use (&$noteCount): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($notes as $note) {
                $person->notes()->attach($note->id);
                $noteCount++;
            }
        });

        // Notes for opportunities
        $this->opportunities->random(50)->each(function (Opportunity $opportunity) use (&$noteCount): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($notes as $note) {
                $opportunity->notes()->attach($note->id);
                $noteCount++;
            }
        });

        // Notes for leads
        $this->leads->random(50)->each(function (Lead $lead) use (&$noteCount): void {
            $notes = Note::factory()
                ->count(random_int(2, 5))
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->users->random()->id,
                ]);

            foreach ($notes as $note) {
                $lead->notes()->attach($note->id);
                $noteCount++;
            }
        });

        $this->command?->info("✓ Created {$noteCount} notes");
    }

    public function seedInvoices(): void
    {
        $this->command?->info('💰 Creating invoices...');

        for ($i = 0; $i < 50; $i++) {
            $invoice = Invoice::factory()->create([
                'team_id' => $this->team->id,
                'company_id' => $this->companies->random()->id,
                'contact_id' => $this->people->random()->id,
                'opportunity_id' => $this->opportunities->random()->id,
                'creator_id' => $this->users->random()->id,
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

        $this->command?->info('✓ Created 50 invoices with line items and payments');
    }

    public function seedSupportCases(): void
    {
        $this->command?->info('🎫 Creating support cases...');

        SupportCase::factory()
            ->count(50)
            ->create([
                'team_id' => $this->team->id,
                'company_id' => fn () => $this->companies->random()->id,
                'contact_id' => fn () => $this->people->random()->id,
                'assigned_to_id' => fn () => $this->users->random()->id,
            ]);

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
                    'title' => $article->title.' v'.($i + 1),
                    'slug' => $article->slug.'-v'.($i + 1),
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
