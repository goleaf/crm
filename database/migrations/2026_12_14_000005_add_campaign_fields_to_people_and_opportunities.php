<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            if (! Schema::hasColumn('people', 'campaign')) {
                $table->string('campaign')->nullable()->after('lead_source');
            }
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            if (! Schema::hasColumn('opportunities', 'lead_source')) {
                $table->string('lead_source')->nullable()->after('forecast_category');
            }

            if (! Schema::hasColumn('opportunities', 'campaign')) {
                $table->string('campaign')->nullable()->after('lead_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            if (Schema::hasColumn('people', 'campaign')) {
                $table->dropColumn('campaign');
            }
        });

        Schema::table('opportunities', function (Blueprint $table): void {
            if (Schema::hasColumn('opportunities', 'campaign')) {
                $table->dropColumn('campaign');
            }

            if (Schema::hasColumn('opportunities', 'lead_source')) {
                $table->dropColumn('lead_source');
            }
        });
    }
};
