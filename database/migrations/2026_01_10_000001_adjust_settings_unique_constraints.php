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
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique(['key']);
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('CREATE UNIQUE INDEX settings_team_key_unique ON settings ((coalesce(team_id, 0)), `key`)');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX settings_team_key_unique ON settings ((coalesce(team_id, 0)), "key")');

            return;
        }

        DB::statement('CREATE UNIQUE INDEX settings_team_key_unique ON settings (coalesce(team_id, 0), "key")');
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('DROP INDEX settings_team_key_unique ON settings');
        } else {
            DB::statement('DROP INDEX settings_team_key_unique');
        }

        Schema::table('settings', function (Blueprint $table): void {
            $table->unique('key');
        });
    }
};
