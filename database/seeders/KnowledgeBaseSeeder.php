<?php

declare(strict_types=1);

namespace Database\Seeders;

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
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating knowledge base (100 articles, 40 FAQs)...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        // Create categories
        $categories = KnowledgeCategory::factory()
            ->count(30)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'creator_id' => fn () => $users->random()->id,
            ]);

        // Create tags
        $tags = KnowledgeTag::factory()
            ->count(100)
            ->create([
                'team_id' => fn () => $teams->random()->id,
            ]);

        // Create articles
        $articles = KnowledgeArticle::factory()
            ->count(500)
            ->create([
                'team_id' => fn () => $teams->random()->id,
                'category_id' => fn () => $categories->random()->id,
                'author_id' => fn () => $users->random()->id,
            ]);

        // Attach tags to articles
        $articleTags = [];
        foreach ($articles as $article) {
            $selectedTags = $tags->random(random_int(1, 5));
            foreach ($selectedTags as $tag) {
                $articleTags[] = [
                    'knowledge_article_id' => $article->id,
                    'knowledge_tag_id' => $tag->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('knowledge_article_knowledge_tag')->insert($articleTags);

        // Create article versions
        $versions = [];
        foreach ($articles->random(300) as $article) {
            for ($i = 0; $i < random_int(1, 5); $i++) {
                $versions[] = [
                    'knowledge_article_id' => $article->id,
                    'team_id' => $article->team_id,
                    'version_number' => $i + 1,
                    'title' => $article->title.' v'.($i + 1),
                    'content' => fake()->paragraphs(5, true),
                    'created_by' => $users->random()->id,
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
                'knowledge_article_id' => $article->id,
                'team_id' => $article->team_id,
                'approver_id' => $users->random()->id,
                'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                'approved_at' => fake()->boolean(70) ? now()->subDays(random_int(1, 90)) : null,
                'comments' => fake()->sentence(),
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
                    'knowledge_article_id' => $article->id,
                    'team_id' => $article->team_id,
                    'user_id' => $users->random()->id,
                    'content' => fake()->paragraph(),
                    'created_at' => now()->subDays(random_int(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($comments, 500) as $chunk) {
            KnowledgeArticleComment::insert($chunk);
        }

        // Create ratings
        $ratings = [];
        foreach ($articles->random(400) as $article) {
            for ($i = 0; $i < random_int(1, 20); $i++) {
                $ratings[] = [
                    'knowledge_article_id' => $article->id,
                    'team_id' => $article->team_id,
                    'user_id' => $users->random()->id,
                    'rating' => random_int(1, 5),
                    'feedback' => fake()->boolean(50) ? fake()->sentence() : null,
                    'created_at' => now()->subDays(random_int(1, 180)),
                    'updated_at' => now(),
                ];
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
                    'knowledge_article_id' => $article->id,
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
                'team_id' => fn () => $teams->random()->id,
                'creator_id' => fn () => $users->random()->id,
            ]);

        // Create template responses
        $templates = [];
        for ($i = 0; $i < 100; $i++) {
            $templates[] = [
                'team_id' => $teams->random()->id,
                'name' => fake()->words(3, true),
                'content' => fake()->paragraphs(3, true),
                'category' => fake()->randomElement(['support', 'sales', 'general']),
                'is_active' => fake()->boolean(90),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        KnowledgeTemplateResponse::insert($templates);

        $this->command->info('✓ Created knowledge base with articles, FAQs, and related data');
    }
}
