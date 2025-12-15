<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table): void {
            $table->foreignId('timesheet_id')->nullable()->after('time_category_id')->constrained('timesheets')->nullOnDelete();
            $table->string('timezone', 64)->nullable()->after('creation_source');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('timesheet_id');
            $table->dropColumn('timezone');
        });
    }
};
