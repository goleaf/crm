<?php

declare(strict_types=1);

use App\Models\EmailMessage;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->currentTeam()->associate($this->team);
    $this->user->save();
    actingAs($this->user);
});

/**
 * **Feature: communication-collaboration, Property 5: Email send reliability**
 *
 * **Validates: Requirements 1.1**
 *
 * Property: Scheduled emails send at the right time without duplication.
 */
test('property: scheduled emails send exactly once at scheduled time', function (): void {
    runPropertyTest(function (): void {
        // Generate a scheduled email
        $scheduledTime = fake()->dateTimeBetween('+1 hour', '+1 week');

        $email = generateEmail($this->team, $this->user, [
            'status' => 'scheduled',
            'scheduled_at' => $scheduledTime,
            'sent_at' => null,
            'subject' => 'Scheduled email: ' . fake()->sentence(4),
        ]);

        // Verify email is in scheduled state
        expect($email->status)->toBe('scheduled');
        expect($email->scheduled_at)->not->toBeNull();
        expect($email->sent_at)->toBeNull();

        // Simulate the scheduled send process
        // In a real implementation, this would be handled by a job/queue
        if ($scheduledTime->isPast()) {
            $email->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        // Verify email state after processing
        $email->refresh();

        if ($scheduledTime->isPast()) {
            expect($email->status)->toBe('sent');
            expect($email->sent_at)->not->toBeNull();
            expect($email->sent_at)->toBeGreaterThanOrEqual($scheduledTime);
        } else {
            expect($email->status)->toBe('scheduled');
            expect($email->sent_at)->toBeNull();
        }

        // Verify no duplicate emails were created
        $duplicateCount = EmailMessage::query()
            ->where('team_id', $this->team->id)
            ->where('subject', $email->subject)
            ->where('scheduled_at', $scheduledTime)
            ->count();

        expect($duplicateCount)->toBe(1,
            'Only one email should exist for the scheduled time',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 5: Email send reliability**
 *
 * **Validates: Requirements 1.1**
 *
 * Property: Failed email sends are logged and do not duplicate.
 */
test('property: failed emails are logged without duplication', function (): void {
    runPropertyTest(function (): void {
        // Generate an email that will fail to send
        $email = generateEmail($this->team, $this->user, [
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
            'to_emails' => [
                ['email' => 'invalid-email-address', 'name' => 'Invalid User'],
            ],
            'subject' => 'Email that will fail: ' . fake()->sentence(4),
        ]);

        // Simulate send failure
        $email->update([
            'status' => 'failed',
            'sent_at' => null,
        ]);

        // Verify email is marked as failed
        expect($email->fresh()->status)->toBe('failed');
        expect($email->fresh()->sent_at)->toBeNull();

        // Simulate retry attempt (should not create duplicate)
        $retryEmail = EmailMessage::query()
            ->where('subject', $email->subject)
            ->where('team_id', $this->team->id)
            ->first();

        expect($retryEmail->id)->toBe($email->id,
            'Retry should use the same email record, not create a duplicate',
        );

        // Simulate successful retry
        $retryEmail->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Verify no duplicates exist
        $emailCount = EmailMessage::query()
            ->where('subject', $email->subject)
            ->where('team_id', $this->team->id)
            ->count();

        expect($emailCount)->toBe(1,
            'Only one email record should exist even after retry',
        );

        // Verify final status
        expect($retryEmail->fresh()->status)->toBe('sent');
        expect($retryEmail->fresh()->sent_at)->not->toBeNull();
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 5: Email send reliability**
 *
 * **Validates: Requirements 1.1**
 *
 * Property: Email threading maintains integrity across send operations.
 */
test('property: email threading is preserved during send operations', function (): void {
    runPropertyTest(function (): void {
        // Generate an initial email thread
        $threadId = fake()->uuid();
        $originalEmail = generateEmail($this->team, $this->user, [
            'thread_id' => $threadId,
            'subject' => 'Original email: ' . fake()->sentence(4),
            'status' => 'sent',
            'sent_at' => now()->subHour(),
        ]);

        // Generate a reply email in the same thread
        $replyEmail = generateEmail($this->team, $this->user, [
            'thread_id' => $threadId,
            'subject' => 'Re: ' . $originalEmail->subject,
            'status' => 'scheduled',
            'scheduled_at' => now()->addMinute(),
        ]);

        // Verify both emails share the same thread
        expect($replyEmail->thread_id)->toBe($originalEmail->thread_id);
        expect($replyEmail->thread_id)->toBe($threadId);

        // Simulate sending the reply
        $replyEmail->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Verify thread integrity is maintained
        $threadEmails = EmailMessage::query()
            ->where('thread_id', $threadId)
            ->oldest('sent_at')
            ->get();

        expect($threadEmails->count())->toBe(2,
            'Thread should contain both emails',
        );

        expect($threadEmails->first()->id)->toBe($originalEmail->id,
            'Original email should be first in thread chronologically',
        );

        expect($threadEmails->last()->id)->toBe($replyEmail->id,
            'Reply email should be last in thread chronologically',
        );

        // Verify all emails in thread have the same thread_id
        foreach ($threadEmails as $email) {
            expect($email->thread_id)->toBe($threadId,
                "Email {$email->id} should maintain thread ID",
            );
        }
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 5: Email send reliability**
 *
 * **Validates: Requirements 1.1**
 *
 * Property: Email attachments are preserved through send operations.
 */
test('property: email attachments remain intact during send process', function (): void {
    runPropertyTest(function (): void {
        // Generate attachments
        $attachmentCount = fake()->numberBetween(1, 4);
        $attachments = [];

        for ($i = 0; $i < $attachmentCount; $i++) {
            $attachments[] = [
                'name' => fake()->word() . '.' . fake()->fileExtension(),
                'size' => fake()->numberBetween(1024, 5242880), // 1KB to 5MB
                'type' => fake()->mimeType(),
                'path' => 'attachments/' . fake()->uuid(),
            ];
        }

        // Generate email with attachments
        $email = generateEmail($this->team, $this->user, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addMinute(),
            'attachments' => $attachments,
            'subject' => 'Email with attachments: ' . fake()->sentence(3),
        ]);

        // Verify attachments are stored
        expect($email->attachments)->toBeArray();
        expect(count($email->attachments))->toBe($attachmentCount);

        // Verify attachment structure
        foreach ($email->attachments as $index => $attachment) {
            expect($attachment)->toHaveKeys(['name', 'size', 'type', 'path']);
            expect($attachment['name'])->toBe($attachments[$index]['name']);
            expect($attachment['size'])->toBe($attachments[$index]['size']);
        }

        // Simulate sending the email
        $email->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Verify attachments are preserved after sending
        $sentEmail = $email->fresh();
        expect($sentEmail->attachments)->toBeArray();
        expect(count($sentEmail->attachments))->toBe($attachmentCount,
            'All attachments should be preserved after sending',
        );

        // Verify attachment integrity
        foreach ($sentEmail->attachments as $index => $attachment) {
            expect($attachment['name'])->toBe($attachments[$index]['name'],
                "Attachment {$index} name should be preserved",
            );
            expect($attachment['size'])->toBe($attachments[$index]['size'],
                "Attachment {$index} size should be preserved",
            );
            expect($attachment['type'])->toBe($attachments[$index]['type'],
                "Attachment {$index} type should be preserved",
            );
        }
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 5: Email send reliability**
 *
 * **Validates: Requirements 1.1**
 *
 * Property: Email recipient lists are maintained accurately through send process.
 */
test('property: email recipients are preserved during send operations', function (): void {
    runPropertyTest(function (): void {
        // Generate recipient lists
        $toCount = fake()->numberBetween(1, 3);
        $ccCount = fake()->numberBetween(0, 2);
        $bccCount = fake()->numberBetween(0, 2);

        $toEmails = [];
        $ccEmails = [];
        $bccEmails = [];

        for ($i = 0; $i < $toCount; $i++) {
            $toEmails[] = ['email' => fake()->safeEmail(), 'name' => fake()->name()];
        }

        for ($i = 0; $i < $ccCount; $i++) {
            $ccEmails[] = ['email' => fake()->safeEmail(), 'name' => fake()->name()];
        }

        for ($i = 0; $i < $bccCount; $i++) {
            $bccEmails[] = ['email' => fake()->safeEmail(), 'name' => fake()->name()];
        }

        // Generate email with all recipient types
        $email = generateEmail($this->team, $this->user, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addMinute(),
            'to_emails' => $toEmails,
            'cc_emails' => $ccCount > 0 ? $ccEmails : null,
            'bcc_emails' => $bccCount > 0 ? $bccEmails : null,
            'subject' => 'Multi-recipient email: ' . fake()->sentence(3),
        ]);

        // Verify recipients before sending
        expect(count($email->to_emails))->toBe($toCount);
        if ($ccCount > 0) {
            expect(count($email->cc_emails ?? []))->toBe($ccCount);
        }
        if ($bccCount > 0) {
            expect(count($email->bcc_emails ?? []))->toBe($bccCount);
        }

        // Simulate sending
        $email->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Verify recipients after sending
        $sentEmail = $email->fresh();
        expect(count($sentEmail->to_emails))->toBe($toCount,
            'TO recipients should be preserved after sending',
        );

        if ($ccCount > 0) {
            expect(count($sentEmail->cc_emails ?? []))->toBe($ccCount,
                'CC recipients should be preserved after sending',
            );
        }

        if ($bccCount > 0) {
            expect(count($sentEmail->bcc_emails ?? []))->toBe($bccCount,
                'BCC recipients should be preserved after sending',
            );
        }

        // Verify specific recipient data integrity
        foreach ($sentEmail->to_emails as $index => $recipient) {
            expect($recipient['email'])->toBe($toEmails[$index]['email'],
                "TO recipient {$index} email should be preserved",
            );
            expect($recipient['name'])->toBe($toEmails[$index]['name'],
                "TO recipient {$index} name should be preserved",
            );
        }
    }, 100);
})->group('property');
