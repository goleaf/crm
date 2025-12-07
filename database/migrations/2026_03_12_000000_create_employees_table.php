<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            // Basic Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();

            // Employment Details
            $table->string('employee_number')->nullable()->unique();
            $table->string('department')->nullable();
            $table->string('role')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('active'); // active, inactive, on_leave, terminated
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Contact Information
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Skills and Certifications
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();

            // Performance and Notes
            $table->text('performance_notes')->nullable();
            $table->decimal('performance_rating', 3, 2)->nullable();

            // Time Off Tracking
            $table->decimal('vacation_days_total', 5, 2)->default(0);
            $table->decimal('vacation_days_used', 5, 2)->default(0);
            $table->decimal('sick_days_total', 5, 2)->default(0);
            $table->decimal('sick_days_used', 5, 2)->default(0);

            // Portal Access
            $table->boolean('has_portal_access')->default(false);

            // Payroll Integration
            $table->string('payroll_id')->nullable();
            $table->json('payroll_metadata')->nullable();

            // Resource Allocation
            $table->decimal('capacity_hours_per_week', 5, 2)->default(40);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'department']);
            $table->index('employee_number');
        });

        // Employee Documents
        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->nullable(); // contract, certificate, review, etc.
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'type']);
        });

        // Employee Time Off Requests
        Schema::create('employee_time_off', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // vacation, sick, personal, etc.
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 2);
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        // Employee Allocation (for tracking resource allocation across projects/tasks)
        Schema::create('employee_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->morphs('allocatable'); // Can be project, task, etc.
            $table->decimal('allocation_percentage', 5, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_allocations');
        Schema::dropIfExists('employee_time_off');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
    }
};
