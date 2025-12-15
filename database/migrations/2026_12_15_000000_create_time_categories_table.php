<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_categories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();

            $table->boolean('is_billable_default')->default(false);
            $table->decimal('default_billing_rate', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('creation_source', 50)->default(CreationSource::WEB->value);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'code']);
            $table->index(['team_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_categories');
    }
};

