<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_manager_assignments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->timestamps();

            $table->index(['team_id', 'manager_id', 'effective_to'], 'employee_manager_assignments_manager_active_index');
            $table->index(['team_id', 'employee_id', 'effective_to'], 'employee_manager_assignments_employee_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_manager_assignments');
    }
};

