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
        Schema::create('company_revenues', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 15, 2);
            $table->char('currency_code', 3)->default(config('company.default_currency', 'USD'));
            $table->string('creation_source', 50)->default(CreationSource::WEB->value);

            $table->timestamps();

            $table->unique(['company_id', 'year']);
            $table->index(['team_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_revenues');
    }
};
