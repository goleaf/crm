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
        Schema::create('calls', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('direction', 20);
            $table->string('phone_number')->nullable();
            $table->string('contact_name')->nullable();

            $table->string('purpose')->nullable();
            $table->text('outcome')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->string('status', 50)->default('scheduled');
            $table->json('participants')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('follow_up_required')->default(false);
            $table->string('voip_call_id')->nullable();
            $table->string('recording_url')->nullable();

            $table->nullableMorphs('related');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};

