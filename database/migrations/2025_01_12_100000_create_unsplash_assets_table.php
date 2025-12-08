<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('unsplash.tables.assets', 'unsplash_assets'), function (Blueprint $table): void {
            $table->id();
            $table->string('unsplash_id')->unique()->index();
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->text('alt_description')->nullable();
            $table->json('urls')->nullable();
            $table->json('links')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('color')->nullable();
            $table->integer('likes')->default(0);
            $table->boolean('liked_by_user')->default(false);

            // Photographer information
            $table->string('photographer_name')->nullable();
            $table->string('photographer_username')->nullable();
            $table->string('photographer_url')->nullable();

            // Download tracking
            $table->string('download_location')->nullable();
            $table->string('local_path')->nullable();
            $table->timestamp('downloaded_at')->nullable();

            // Metadata
            $table->json('exif')->nullable();
            $table->json('location')->nullable();
            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['photographer_username', 'created_at']);
            $table->index('downloaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('unsplash.tables.assets', 'unsplash_assets'));
    }
};
