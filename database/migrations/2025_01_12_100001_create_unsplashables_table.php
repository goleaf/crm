<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('unsplash.tables.pivot', 'unsplashables'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unsplash_asset_id')
                ->constrained(config('unsplash.tables.assets', 'unsplash_assets'))
                ->cascadeOnDelete();
            $table->morphs('unsplashable');
            $table->string('collection')->nullable()->index();
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['unsplash_asset_id', 'unsplashable_type', 'unsplashable_id', 'collection'], 'unsplashables_unique');
            $table->index(['unsplashable_type', 'unsplashable_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('unsplash.tables.pivot', 'unsplashables'));
    }
};
