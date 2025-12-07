<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update type enum to include all AccountType values
        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        if (DB::connection()->getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table
            Schema::table('accounts', function (Blueprint $table): void {
                $table->dropColumn('type');
            });

            Schema::table('accounts', function (Blueprint $table): void {
                $table->enum('type', ['customer', 'prospect', 'partner', 'vendor', 'competitor', 'investor', 'reseller'])
                    ->after('parent_id');
            });
        } else {
            // For other databases, use ALTER
            DB::statement("ALTER TABLE accounts MODIFY COLUMN type ENUM('customer', 'prospect', 'partner', 'vendor', 'competitor', 'investor', 'reseller')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert type enum to original values
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('accounts', function (Blueprint $table): void {
                $table->dropColumn('type');
            });

            Schema::table('accounts', function (Blueprint $table): void {
                $table->enum('type', ['customer', 'prospect', 'partner', 'vendor'])
                    ->after('parent_id');
            });
        } else {
            DB::statement("ALTER TABLE accounts MODIFY COLUMN type ENUM('customer', 'prospect', 'partner', 'vendor')");
        }
    }
};
