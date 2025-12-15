<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->decimal('default_billing_rate', 10, 2)->nullable()->after('capacity_hours_per_week');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->decimal('billing_rate', 10, 2)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('default_billing_rate');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('billing_rate');
        });
    }
};

