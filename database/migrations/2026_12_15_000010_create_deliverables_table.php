<?php

declare(strict_types=1);

use App\Enums\DeliverableStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverables', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->text('acceptance_criteria')->nullable();
            $table->string('status', 20)->default(DeliverableStatus::PENDING->value);

            $table->string('completion_evidence_url')->nullable();
            $table->string('completion_evidence_path')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('milestone_id', 'deliverables_milestone_index');
            $table->index('due_date', 'deliverables_due_date_index');
            $table->index('owner_id', 'deliverables_owner_index');
            $table->index('status', 'deliverables_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};

