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
        Schema::table('task_user', function (Blueprint $table): void {
            $table->timestamp('notified_at')->nullable()->after('user_id');
            $table->index(['task_id', 'user_id', 'notified_at']);
        });

        DB::table('task_user')
            ->whereNull('notified_at')
            ->update([
                'notified_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('task_user', function (Blueprint $table): void {
            $table->dropIndex(['task_id', 'user_id', 'notified_at']);
            $table->dropColumn('notified_at');
        });
    }
};

