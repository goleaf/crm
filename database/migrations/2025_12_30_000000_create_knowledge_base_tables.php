<?php

declare(strict_types=1);

use App\Enums\Knowledge\ApprovalStatus;
use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use App\Enums\Knowledge\CommentStatus;
use App\Enums\Knowledge\FaqStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_categories')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('visibility', 32)->default(ArticleVisibility::INTERNAL->value);
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'slug']);
            $table->index(['team_id', 'position']);
        });

        Schema::create('knowledge_articles', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->string('status', 32)->default(ArticleStatus::DRAFT->value);
            $table->string('visibility', 32)->default(ArticleVisibility::INTERNAL->value);

            $table->string('summary', 1024)->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            $table->boolean('allow_comments')->default(true);
            $table->boolean('allow_ratings')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('review_due_at')->nullable();

            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);

            $table->text('approval_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'slug']);
            $table->index(['status', 'visibility']);
        });

        Schema::create('knowledge_article_versions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('version');
            $table->string('status', 32)->default(ArticleStatus::DRAFT->value);
            $table->string('visibility', 32)->default(ArticleVisibility::INTERNAL->value);

            $table->string('title');
            $table->string('slug');
            $table->string('summary', 1024)->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            $table->text('change_notes')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['article_id', 'version']);
            $table->index(['article_id', 'version']);
        });

        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->foreignId('current_version_id')
                ->nullable()
                ->after('approver_id')
                ->constrained('knowledge_article_versions')
                ->nullOnDelete();
        });

        Schema::create('knowledge_tags', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('description', 512)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'slug']);
        });

        Schema::create('knowledge_article_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('knowledge_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'tag_id']);
        });

        Schema::create('knowledge_article_approvals', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 32)->default(ApprovalStatus::PENDING->value);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();

            $table->timestamps();
        });

        Schema::create('knowledge_article_comments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_article_comments')->nullOnDelete();

            $table->text('body');
            $table->string('status', 32)->default(CommentStatus::PENDING->value);
            $table->boolean('is_internal')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_id', 'status']);
        });

        Schema::create('knowledge_article_ratings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('feedback')->nullable();
            $table->string('context', 64)->default('web');
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->unique(['article_id', 'user_id']);
        });

        Schema::create('knowledge_article_relations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('related_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->string('relation_type', 64)->default('related');

            $table->timestamps();

            $table->unique(['article_id', 'related_article_id']);
        });

        Schema::create('knowledge_faqs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained('knowledge_articles')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('question');
            $table->text('answer');
            $table->string('status', 32)->default(FaqStatus::DRAFT->value);
            $table->string('visibility', 32)->default(ArticleVisibility::PUBLIC->value);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
        });

        Schema::create('knowledge_template_responses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('body');
            $table->string('visibility', 32)->default(ArticleVisibility::INTERNAL->value);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_template_responses');
        Schema::dropIfExists('knowledge_faqs');
        Schema::dropIfExists('knowledge_article_relations');
        Schema::dropIfExists('knowledge_article_ratings');
        Schema::dropIfExists('knowledge_article_comments');
        Schema::dropIfExists('knowledge_article_approvals');
        Schema::dropIfExists('knowledge_article_tag');
        Schema::dropIfExists('knowledge_tags');

        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_version_id');
        });

        Schema::dropIfExists('knowledge_article_versions');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
    }
};
