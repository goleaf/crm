<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            if (! Schema::hasColumn('leads', 'description')) {
                $table->text('description')->nullable()->after('company_name');
            }

            if (! Schema::hasColumn('leads', 'lead_value')) {
                $table->decimal('lead_value', 15, 2)->nullable()->after('description');
            }

            if (! Schema::hasColumn('leads', 'lead_type')) {
                $table->string('lead_type', 50)->nullable()->after('lead_value');
            }

            if (! Schema::hasColumn('leads', 'expected_close_date')) {
                $table->date('expected_close_date')->nullable()->after('lead_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'expected_close_date')) {
                $table->dropColumn('expected_close_date');
            }

            if (Schema::hasColumn('leads', 'lead_type')) {
                $table->dropColumn('lead_type');
            }

            if (Schema::hasColumn('leads', 'lead_value')) {
                $table->dropColumn('lead_value');
            }

            if (Schema::hasColumn('leads', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
