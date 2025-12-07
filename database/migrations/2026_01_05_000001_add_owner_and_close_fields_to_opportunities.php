<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->foreignId('owner_id')
                ->nullable()
                ->after('creator_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('closed_at')->nullable()->after('order_column');
            $table->foreignId('closed_by_id')
                ->nullable()
                ->after('closed_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('opportunities')
            ->whereNull('owner_id')
            ->update(['owner_id' => DB::raw('creator_id')]);
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['closed_by_id']);

            $table->dropColumn([
                'owner_id',
                'closed_at',
                'closed_by_id',
            ]);
        });
    }
};
