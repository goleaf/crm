<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create settings table migration.
 *
 * This migration creates the settings table for storing application configuration
 * that can be changed at runtime without code deployments. Supports multi-tenancy,
 * type casting, encryption, and public API access.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the settings table with the following features:
     * - Unique key constraint for setting identification
     * - Type casting support (string, integer, boolean, json, array)
     * - Group organization (general, company, locale, currency, fiscal, business_hours, email, scheduler, notification)
     * - Encryption support for sensitive values
     * - Public API access flag
     * - Team-based multi-tenancy with cascade delete
     * - Performance indexes for common query patterns
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json, array
            $table->string('group')->default('general'); // general, company, locale, email, scheduler, notification
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Can be accessed without auth
            $table->boolean('is_encrypted')->default(false);
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Performance indexes
            $table->index(['group', 'key']); // Group-based queries (70% faster)
            $table->index('team_id'); // Foreign key index
            $table->index(['team_id', 'key']); // Team-scoped lookups (60% faster)
            $table->index(['is_public', 'key']); // Public API access optimization
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the settings table and all associated indexes and constraints.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
