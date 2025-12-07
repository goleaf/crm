<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->foreignId('account_id')
                ->nullable()
                ->after('team_id')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->index('account_id');
        });

        Schema::table('cases', function (Blueprint $table): void {
            $table->foreignId('account_id')
                ->nullable()
                ->after('team_id')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });

        Schema::table('cases', function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
