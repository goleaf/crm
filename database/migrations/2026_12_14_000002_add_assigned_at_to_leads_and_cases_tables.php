<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (! Schema::hasColumn('leads', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_to_id');
            }
        });

        Schema::table('cases', function (Blueprint $table): void {
            if (! Schema::hasColumn('cases', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_to_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
        });

        Schema::table('cases', function (Blueprint $table): void {
            if (Schema::hasColumn('cases', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
        });
    }
};
