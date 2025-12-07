<?php

declare(strict_types=1);

use App\Enums\EmailProgramStatus;
use App\Enums\EmailProgramType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default(EmailProgramType::DRIP->value);
            $table->string('status')->default(EmailProgramStatus::DRAFT->value);

            // Audience and segmentation
            $table->json('audience_filters')->nullable();
            $table->integer('estimated_audience_size')->default(0);

            // Scheduling
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // A/B Testing
            $table->boolean('is_ab_test')->default(false);
            $table->integer('ab_test_sample_size_percent')->nullable();
            $table->string('ab_test_winner_metric')->nullable(); // open_rate, click_rate, conversion_rate
            $table->timestamp('ab_test_winner_selected_at')->nullable();
            $table->string('ab_test_winner_variant')->nullable();

            // Personalization
            $table->json('personalization_rules')->nullable();
            $table->json('dynamic_content_blocks')->nullable();

            // Scoring and engagement
            $table->json('scoring_rules')->nullable();
            $table->integer('min_engagement_score')->default(0);

            // Deliverability
            $table->integer('throttle_rate_per_hour')->nullable();
            $table->json('send_time_optimization')->nullable();
            $table->boolean('respect_quiet_hours')->default(true);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();

            // Analytics
            $table->integer('total_recipients')->default(0);
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_opened')->default(0);
            $table->integer('total_clicked')->default(0);
            $table->integer('total_bounced')->default(0);
            $table->integer('total_unsubscribed')->default(0);
            $table->integer('total_complained')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'type']);
            $table->index('scheduled_start_at');
        });

        Schema::create('email_program_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_program_id')->constrained()->cascadeOnDelete();
            $table->integer('step_order')->default(0);
            $table->string('name');
            $table->text('description')->nullable();

            // Email content
            $table->string('subject_line');
            $table->string('preview_text')->nullable();
            $table->text('html_content')->nullable();
            $table->text('plain_text_content')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to_email')->nullable();

            // A/B Testing variants
            $table->string('variant_name')->nullable(); // A, B, C, etc.
            $table->boolean('is_control')->default(false);

            // Timing
            $table->integer('delay_value')->default(0); // numeric value
            $table->string('delay_unit')->default('days'); // minutes, hours, days, weeks
            $table->json('conditional_send_rules')->nullable();

            // Tracking
            $table->integer('recipients_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);

            $table->timestamps();

            $table->index(['email_program_id', 'step_order']);
            $table->index(['email_program_id', 'variant_name']);
        });

        Schema::create('email_program_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_program_step_id')->nullable()->constrained()->nullOnDelete();

            // Recipient info
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->json('custom_fields')->nullable();

            // Polymorphic relation to source record
            $table->morphs('recipient');

            // Status tracking
            $table->string('status')->default('pending');
            $table->timestamp('scheduled_send_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            // Engagement tracking
            $table->integer('open_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('engagement_score')->default(0);

            // Bounce/error handling
            $table->string('bounce_type')->nullable();
            $table->text('bounce_reason')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['email_program_id', 'status']);
            $table->index(['email_program_id', 'email']);
            $table->index('scheduled_send_at');
        });

        Schema::create('email_program_unsubscribes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->foreignId('email_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->text('feedback')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'email']);
        });

        Schema::create('email_program_bounces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_program_recipient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->index();
            $table->string('bounce_type');
            $table->text('bounce_reason')->nullable();
            $table->text('diagnostic_code')->nullable();
            $table->json('raw_message')->nullable();
            $table->timestamps();

            $table->index(['email_program_id', 'bounce_type']);
            $table->index(['email', 'bounce_type']);
        });

        Schema::create('email_program_analytics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_program_step_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');

            // Daily metrics
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('unique_opens')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('unique_clicks')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);
            $table->integer('complained_count')->default(0);

            // Calculated rates
            $table->decimal('delivery_rate', 5, 2)->default(0);
            $table->decimal('open_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->decimal('bounce_rate', 5, 2)->default(0);
            $table->decimal('unsubscribe_rate', 5, 2)->default(0);

            $table->timestamps();

            $table->unique(['email_program_id', 'email_program_step_id', 'date'], 'email_analytics_unique');
            $table->index(['email_program_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_program_analytics');
        Schema::dropIfExists('email_program_bounces');
        Schema::dropIfExists('email_program_unsubscribes');
        Schema::dropIfExists('email_program_recipients');
        Schema::dropIfExists('email_program_steps');
        Schema::dropIfExists('email_programs');
    }
};
