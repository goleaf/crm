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
        Schema::create('email_messages', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();

            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();

            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->json('attachments')->nullable();

            $table->string('thread_id')->nullable();
            $table->string('folder')->nullable();

            $table->string('status', 50)->default('draft');
            $table->string('importance', 20)->default('normal');

            $table->boolean('read_receipt_requested')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->nullableMorphs('related');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'thread_id']);
            $table->index(['team_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
