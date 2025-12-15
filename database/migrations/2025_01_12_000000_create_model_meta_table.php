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
        Schema::create('model_meta', function (Blueprint $table): void {
            $table->id();

            $table->morphs('metable');

            $table->string('type')->default('null')->index();
            $table->string('key')->index();
            $table->text('value')->nullable();

            $table->timestamps();

            $table->unique(['metable_type', 'metable_id', 'key'], 'model_meta_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_meta');
    }
};
