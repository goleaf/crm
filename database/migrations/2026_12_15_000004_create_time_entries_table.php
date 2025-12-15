<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->date('date');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('duration_minutes');

            $table->text('description');
            $table->text('notes')->nullable();

            $table->boolean('is_billable')->default(false);
            $table->decimal('billing_rate', 10, 2)->nullable();
            $table->decimal('billing_amount', 10, 2)->nullable();

            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('time_category_id')->nullable()->constrained('time_categories')->nullOnDelete();

            $table->string('approval_status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('creation_source', 50)->default(CreationSource::WEB->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'date'], 'time_entries_employee_date_index');
            $table->index('project_id', 'time_entries_project_index');
            $table->index('company_id', 'time_entries_company_index');
            $table->index('is_billable', 'time_entries_billable_index');
            $table->index('approval_status', 'time_entries_approval_status_index');
            $table->index('date', 'time_entries_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
