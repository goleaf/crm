<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_merges', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('primary_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('duplicate_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('merged_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('field_selections')->nullable();
            $table->json('transferred_relationships')->nullable();

            $table->timestamps();

            // Add indexes for querying merge history
            $table->index('primary_company_id');
            $table->index('duplicate_company_id');
            $table->index('merged_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_merges');
    }
};
