<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('db-config.table_name', 'db_config');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('key');
            $table->json('settings')->nullable();
            $table->unique(['group', 'key']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('db-config.table_name', 'db_config'));
    }
};
