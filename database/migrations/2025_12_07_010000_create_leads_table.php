<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('qualified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('converted_contact_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('converted_opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('import_id')->nullable()->constrained('imports')->nullOnDelete();

            $table->string('name');
            $table->string('job_title')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();

            $table->string('source', 50)->default(LeadSource::WEBSITE->value);
            $table->string('status', 50)->default(LeadStatus::NEW->value);
            $table->unsignedSmallInteger('score')->default(0);
            $table->string('grade', 10)->nullable();
            $table->string('assignment_strategy', 50)->default(LeadAssignmentStrategy::MANUAL->value);
            $table->string('territory')->nullable();

            $table->string('nurture_status', 50)->default(LeadNurtureStatus::NOT_STARTED->value);
            $table->string('nurture_program')->nullable();
            $table->timestamp('next_nurture_touch_at')->nullable();

            $table->timestamp('qualified_at')->nullable();
            $table->text('qualification_notes')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->timestamp('last_activity_at')->nullable();

            $table->decimal('duplicate_score', 5, 2)->nullable();
            $table->string('web_form_key')->nullable();
            $table->json('web_form_payload')->nullable();

            $table->string('creation_source', 50)->default(CreationSource::WEB->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'assigned_to_id']);
            $table->index('email');
            $table->index('phone');
            $table->index('company_name');
            $table->index('source');
            $table->index('territory');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
