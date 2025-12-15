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
        Schema::table('people', function (Blueprint $table): void {
            $table->string('lead_source')->nullable()->after('social_links');
            $table->boolean('is_portal_user')->default(false)->after('lead_source');
            $table->string('portal_username')->nullable()->after('is_portal_user');
            $table->timestamp('portal_last_login_at')->nullable()->after('portal_username');

            $table->boolean('sync_enabled')->default(false)->after('portal_last_login_at');
            $table->string('sync_reference')->nullable()->after('sync_enabled');
            $table->timestamp('synced_at')->nullable()->after('sync_reference');

            $table->json('segments')->nullable()->after('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropColumn([
                'lead_source',
                'is_portal_user',
                'portal_username',
                'portal_last_login_at',
                'sync_enabled',
                'sync_reference',
                'synced_at',
                'segments',
            ]);
        });
    }
};
