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
        Schema::create('absences', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('duration_days', 5, 2);
            $table->decimal('duration_hours', 6, 2);

            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();

            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('creation_source', 50)->default(CreationSource::WEB->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'start_date', 'end_date'], 'absences_employee_dates_index');
            $table->index('leave_type_id', 'absences_leave_type_index');
            $table->index('status', 'absences_status_index');
            $table->index(['start_date', 'end_date'], 'absences_date_range_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};

