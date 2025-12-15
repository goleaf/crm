<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unsplash_assets')) {
            Schema::create('unsplash_assets', function (Blueprint $table): void {
                $table->id();
                $table->string('unsplash_id')->unique();
                $table->text('description')->nullable();
                $table->text('alt_description')->nullable();
                $table->json('urls'); // raw, full, regular, small, thumb
                $table->json('links'); // self, html, download, download_location
                $table->json('user'); // id, username, name, portfolio_url, profile_image
                $table->integer('width');
                $table->integer('height');
                $table->string('color')->nullable();
                $table->string('blur_hash')->nullable();
                $table->integer('likes')->default(0);
                $table->timestamp('promoted_at')->nullable();
                $table->string('download_location')->nullable(); // For tracking
                $table->string('local_path')->nullable(); // If downloaded
                $table->string('local_disk')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('unsplashables')) {
            Schema::create('unsplashables', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('unsplash_asset_id')->constrained()->cascadeOnDelete();
                $table->morphs('unsplashable');
                $table->string('collection')->default('default');
                $table->integer('order')->default(0);
                $table->json('metadata')->nullable(); // caption, alt_text override, etc.
                $table->timestamps();

                $table->index(['unsplashable_type', 'unsplashable_id', 'collection']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unsplashables');
        Schema::dropIfExists('unsplash_assets');
    }
};
