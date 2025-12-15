<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->string('status', 20)->default('draft'); // draft, pending, approved, rejected

            $table->timestamp('submitted_at')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('reminder_24h_sent_at')->nullable();
            $table->timestamp('reminder_deadline_day_sent_at')->nullable();
            $table->timestamp('auto_submitted_at')->nullable();

            $table->timestamps();

            $table->unique(['team_id', 'employee_id', 'period_start', 'period_end'], 'timesheets_employee_period_unique');
            $table->index(['team_id', 'manager_id', 'status'], 'timesheets_manager_status_index');
            $table->index(['team_id', 'employee_id', 'status'], 'timesheets_employee_status_index');
            $table->index(['team_id', 'period_start', 'period_end'], 'timesheets_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};

