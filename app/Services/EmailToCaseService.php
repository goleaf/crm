<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\CreationSource;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Team;
use App\Support\PersonNameFormatter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final readonly class EmailToCaseService
{
    public function __construct(
        private CaseQueueRoutingService $queueRoutingService,
        private CaseSlaService $slaService,
    ) {}

    /**
     * Create a case from an incoming email.
     *
     * @param  array<string, mixed>  $emailData
     */
    public function createFromEmail(array $emailData, Team $team): SupportCase
    {
        if (! Config::get('cases.email_to_case.enabled', true)) {
            throw new \RuntimeException('Email-to-case is not enabled');
        }

        // Check for existing thread
        $existingCase = $this->findExistingThread($emailData['message_id'] ?? null, $emailData['in_reply_to'] ?? null);

        if ($existingCase instanceof \App\Models\SupportCase) {
            $this->addEmailToThread($existingCase, $emailData);

            return $existingCase;
        }

        // Find or create contact
        $contact = $this->findOrCreateContact($emailData['from_email'], $emailData['from_name'] ?? null, $team);

        // Create new case
        $case = new SupportCase([
            'team_id' => $team->id,
            'case_number' => $this->generateCaseNumber(),
            'subject' => $emailData['subject'] ?? 'No Subject',
            'description' => $emailData['body'] ?? '',
            'status' => CaseStatus::NEW,
            'priority' => CasePriority::from(Config::get('cases.email_to_case.default_priority', CasePriority::P3->value)),
            'type' => CaseType::from(Config::get('cases.email_to_case.default_type', CaseType::QUESTION->value)),
            'channel' => CaseChannel::EMAIL,
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'email_message_id' => $emailData['message_id'] ?? null,
            'thread_reference' => $emailData['message_id'] ?? null,
            'creation_source' => CreationSource::EMAIL,
        ]);

        $case->save();

        // Set SLA due date
        $slaDueDate = $this->slaService->calculateSlaDueDate($case);
        if ($slaDueDate instanceof \Illuminate\Support\Carbon) {
            $case->update(['sla_due_at' => $slaDueDate]);
        }

        // Assign to queue
        if (Config::get('cases.email_to_case.auto_assign', true)) {
            $this->queueRoutingService->assignQueue($case);
        }

        return $case;
    }

    /**
     * Find an existing case thread based on email message ID or in-reply-to header.
     */
    private function findExistingThread(?string $messageId, ?string $inReplyTo): ?SupportCase
    {
        if (! Config::get('cases.email_to_case.thread_tracking', true)) {
            return null;
        }

        if ($inReplyTo !== null) {
            $case = SupportCase::where('email_message_id', $inReplyTo)
                ->orWhere('thread_reference', $inReplyTo)
                ->first();

            if ($case !== null) {
                return $case;
            }
        }

        if ($messageId !== null) {
            return SupportCase::where('thread_reference', $messageId)->first();
        }

        return null;
    }

    /**
     * Add an email to an existing case thread.
     *
     * @param  array<string, mixed>  $emailData
     */
    private function addEmailToThread(SupportCase $case, array $emailData): void
    {
        // Add note to case with email content
        $case->notes()->create([
            'team_id' => $case->team_id,
            'creator_id' => null, // Email from customer
            'content' => $emailData['body'] ?? '',
            'category' => 'email',
        ]);

        // Record first response if this is from support
        if ($this->isFromSupport()) {
            $this->slaService->recordFirstResponse($case);
        }
    }

    /**
     * Find or create a contact from email address.
     */
    private function findOrCreateContact(string $email, ?string $name, Team $team): People
    {
        // Try to find existing contact by email
        $contact = People::whereHas('emails', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($email): void {
            $query->where('email', $email);
        })->first();

        if ($contact !== null) {
            return $contact;
        }

        // Create new contact
        $contact = People::create([
            'team_id' => $team->id,
            'name' => PersonNameFormatter::full($name, $email),
            'primary_email' => $email,
            'creation_source' => CreationSource::EMAIL,
        ]);

        // Add email
        $contact->emails()->create([
            'email' => $email,
            'type' => 'work',
            'is_primary' => true,
        ]);

        return $contact;
    }

    /**
     * Check if email is from support team.
     */
    private function isFromSupport(): bool
    {
        // TODO: Implement logic to determine if email is from support
        // This could check against support email addresses or domains
        return false;
    }

    /**
     * Generate a unique case number.
     */
    private function generateCaseNumber(): string
    {
        $prefix = 'CASE';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
