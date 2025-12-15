<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('people_id')->unique()->constrained('people')->cascadeOnDelete();
            $table->boolean('email_opt_in')->default(true);
            $table->boolean('phone_opt_in')->default(true);
            $table->boolean('sms_opt_in')->default(true);
            $table->boolean('postal_opt_in')->default(true);
            $table->string('preferred_channel', 50)->nullable();
            $table->string('preferred_time', 50)->nullable();
            $table->boolean('do_not_contact')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_preferences');
    }
};
