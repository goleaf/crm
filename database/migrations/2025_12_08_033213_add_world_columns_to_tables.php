<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
        $prefix = $isSqlite ? '' : 'world.';

        Schema::table('companies', function (Blueprint $table) use ($prefix): void {
            $table->foreignId('billing_country_id')->nullable()->after('billing_country')->constrained($prefix . 'countries')->nullOnDelete();
            $table->foreignId('billing_state_id')->nullable()->after('billing_state')->constrained($prefix . 'states')->nullOnDelete();
            $table->foreignId('billing_city_id')->nullable()->after('billing_city')->constrained($prefix . 'cities')->nullOnDelete();

            $table->foreignId('shipping_country_id')->nullable()->after('shipping_country')->constrained($prefix . 'countries')->nullOnDelete();
            $table->foreignId('shipping_state_id')->nullable()->after('shipping_state')->constrained($prefix . 'states')->nullOnDelete();
            $table->foreignId('shipping_city_id')->nullable()->after('shipping_city')->constrained($prefix . 'cities')->nullOnDelete();
        });

        Schema::table('people', function (Blueprint $table) use ($prefix): void {
            $table->foreignId('address_country_id')->nullable()->after('address_country')->constrained($prefix . 'countries')->nullOnDelete();
            $table->foreignId('address_state_id')->nullable()->after('address_state')->constrained($prefix . 'states')->nullOnDelete();
            $table->foreignId('address_city_id')->nullable()->after('address_city')->constrained($prefix . 'cities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropForeign(['billing_country_id']);
            $table->dropForeign(['billing_state_id']);
            $table->dropForeign(['billing_city_id']);
            $table->dropColumn(['billing_country_id', 'billing_state_id', 'billing_city_id']);

            $table->dropForeign(['shipping_country_id']);
            $table->dropForeign(['shipping_state_id']);
            $table->dropForeign(['shipping_city_id']);
            $table->dropColumn(['shipping_country_id', 'shipping_state_id', 'shipping_city_id']);
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->dropForeign(['address_country_id']);
            $table->dropForeign(['address_state_id']);
            $table->dropForeign(['address_city_id']);
            $table->dropColumn(['address_country_id', 'address_state_id', 'address_city_id']);
        });
    }
};
