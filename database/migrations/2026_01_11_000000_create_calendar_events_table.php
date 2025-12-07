<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarSyncStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('type', 50)->default(CalendarEventType::MEETING->value);
            $table->string('status', 50)->default(CalendarEventStatus::SCHEDULED->value);
            $table->boolean('is_all_day')->default(false);

            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->unsignedInteger('reminder_minutes_before')->nullable();

            $table->json('attendees')->nullable();

            $table->nullableMorphs('related');

            $table->string('sync_provider')->nullable();
            $table->string('sync_status', 50)->default(CalendarSyncStatus::NOT_SYNCED->value);
            $table->string('sync_external_id')->nullable();

            $table->string('creation_source', 50)->default(\App\Enums\CreationSource::WEB->value);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'start_at']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
