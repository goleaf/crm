<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Password policies table
        Schema::create('password_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('min_length')->default(8);
            $table->integer('max_length')->default(128);
            $table->boolean('require_uppercase')->default(true);
            $table->boolean('require_lowercase')->default(true);
            $table->boolean('require_numbers')->default(true);
            $table->boolean('require_symbols')->default(false);
            $table->integer('password_history_count')->default(5);
            $table->integer('max_age_days')->nullable();
            $table->integer('lockout_attempts')->default(5);
            $table->integer('lockout_duration_minutes')->default(15);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Login history table
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('location')->nullable();
            $table->string('device')->nullable();
            $table->boolean('successful')->default(true);
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index(['user_id', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
        });

        // User activity tracking table
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'created_at']);
        });

        // User sessions table (for session management)
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->timestamp('last_activity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['session_id', 'is_active']);
        });

        // Password history table
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('password_hash');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Add admin panel related columns to users table (only if they don't exist)
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default('regular'); // admin, regular
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active'); // active, inactive, suspended, locked
            }
            if (!Schema::hasColumn('users', 'password_expires_at')) {
                $table->timestamp('password_expires_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->integer('failed_login_attempts')->default(0);
            }
            if (!Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable();
            }
            if (!Schema::hasColumn('users', 'password_policy_id')) {
                $table->foreignId('password_policy_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'force_password_change')) {
                $table->boolean('force_password_change')->default(false);
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }
            // two_factor_secret and two_factor_recovery_codes already exist from Jetstream
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_policy_id')) {
                $table->dropForeign(['password_policy_id']);
            }
            
            $columnsToCheck = [
                'user_type',
                'status',
                'password_expires_at',
                'last_login_at',
                'failed_login_attempts',
                'locked_until',
                'password_policy_id',
                'force_password_change',
                'two_factor_enabled',
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('password_histories');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('user_activities');
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('password_policies');
    }
};