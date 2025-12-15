<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();

            $table->unsignedInteger('year');

            $table->decimal('allocated_days', 5, 2)->default(0);
            $table->decimal('used_days', 5, 2)->default(0);
            $table->decimal('pending_days', 5, 2)->default(0);
            $table->decimal('available_days', 5, 2)->default(0);
            $table->decimal('carried_over_days', 5, 2)->default(0);

            $table->date('expires_at')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year'], 'unique_employee_leave_year');
            $table->index(['employee_id', 'year'], 'leave_balances_employee_year_index');
            $table->index('leave_type_id', 'leave_balances_leave_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
