<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_role_people', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('contact_role_id')->constrained('contact_roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['people_id', 'contact_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_role_people');
    }
};
