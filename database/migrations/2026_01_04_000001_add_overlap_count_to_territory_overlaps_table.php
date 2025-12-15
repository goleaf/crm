<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('territory_overlaps')) {
            return;
        }

        Schema::table('territory_overlaps', function (Blueprint $table): void {
            if (! Schema::hasColumn('territory_overlaps', 'overlap_count')) {
                $table->unsignedInteger('overlap_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('territory_overlaps')) {
            return;
        }

        Schema::table('territory_overlaps', function (Blueprint $table): void {
            if (Schema::hasColumn('territory_overlaps', 'overlap_count')) {
                $table->dropColumn('overlap_count');
            }
        });
    }
};
