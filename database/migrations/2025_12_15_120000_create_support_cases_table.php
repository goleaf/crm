<?php

declare(strict_types=1);

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\CreationSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->nullOnDelete();

            $table->string('case_number')->unique();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('status', 50)->default(CaseStatus::NEW->value);
            $table->string('priority', 50)->default(CasePriority::P3->value);
            $table->string('type', 50)->default(CaseType::QUESTION->value);
            $table->string('channel', 50)->default(CaseChannel::INTERNAL->value);

            $table->string('queue')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->string('thread_reference')->nullable();
            $table->string('customer_portal_url')->nullable();
            $table->string('knowledge_base_reference')->nullable();
            $table->string('email_message_id')->nullable();

            $table->string('creation_source', 50)->default(CreationSource::WEB->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['queue', 'priority']);
            $table->index(['channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
