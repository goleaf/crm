<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->json('addresses')->nullable()->after('shipping_address');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->json('addresses')->nullable()->after('shipping_country');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('addresses');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('addresses');
        });
    }
};
