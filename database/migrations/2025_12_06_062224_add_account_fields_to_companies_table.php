<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('website')->nullable()->after('name');
            $table->string('industry')->nullable()->after('website');
            $table->decimal('revenue', 15, 2)->nullable()->after('industry');
            $table->integer('employee_count')->nullable()->after('revenue');
            $table->text('description')->nullable()->after('employee_count');

            // Add indexes for duplicate detection
            $table->index('name');
            $table->index('website');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['name']);
            $table->dropIndex(['website']);

            $table->dropColumn([
                'website',
                'industry',
                'revenue',
                'employee_count',
                'description',
            ]);
        });
    }
};
