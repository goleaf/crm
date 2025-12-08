<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_reference_counters')) {
            return;
        }

        Schema::create('model_reference_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedBigInteger('value')->default(1);
            $table->timestamps();

            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_reference_counters');
    }
};
