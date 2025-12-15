<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add meeting-specific fields and recurrence support to calendar events.
 *
 * This migration implements Communication & Collaboration specification requirements:
 * - Requirement 3.1: Meeting management with recurrence, attendees, reminders, agenda/minutes
 * - Property 7: Recurring rules generate correct instances without duplication
 *
 * Adds the following fields:
 * - recurrence_rule: Pattern for recurring events (DAILY, WEEKLY, MONTHLY, YEARLY)
 * - recurrence_end_date: When recurring events should stop
 * - recurrence_parent_id: Links recurring instances to parent event
 * - agenda: Rich text meeting agenda (inherited by instances)
 * - minutes: Rich text meeting minutes (instance-specific)
 * - room_booking: Conference room or space reservation
 *
 * @see \App\Models\CalendarEvent
 * @see \App\Services\RecurrenceService
 * @see \App\Observers\CalendarEventObserver
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds recurrence and meeting-specific fields to the calendar_events table.
     * Foreign key constraint ensures referential integrity for recurring instances.
     */
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            // Recurrence fields
            $table->string('recurrence_rule')->nullable()->after('reminder_minutes_before');
            $table->timestamp('recurrence_end_date')->nullable()->after('recurrence_rule');
            $table->foreignId('recurrence_parent_id')->nullable()->constrained('calendar_events')->nullOnDelete()->after('recurrence_end_date');

            // Meeting-specific fields
            $table->text('agenda')->nullable()->after('notes');
            $table->text('minutes')->nullable()->after('agenda');
            $table->string('room_booking')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops all added columns and foreign key constraints.
     * Safe to rollback as it removes only the fields added in up().
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropColumn([
                'recurrence_rule',
                'recurrence_end_date',
                'recurrence_parent_id',
                'agenda',
                'minutes',
                'room_booking',
            ]);
        });
    }
};
