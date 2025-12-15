<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
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
 * Integration test for email-to-record association functionality.
 *
 * **Validates: Requirements 1.3**
 */
test('emails can be associated with CRM records', function (): void {
    // Create various CRM records
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $lead = Lead::factory()->create(['team_id' => $this->team->id]);
    $opportunity = Opportunity::factory()->create(['team_id' => $this->team->id]);

    // Create emails associated with each record type
    $companyEmail = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Email to company',
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    $personEmail = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Email to person',
        'related_id' => $person->id,
        'related_type' => People::class,
    ]);

    $leadEmail = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Email to lead',
        'related_id' => $lead->id,
        'related_type' => Lead::class,
    ]);

    $opportunityEmail = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Email to opportunity',
        'related_id' => $opportunity->id,
        'related_type' => Opportunity::class,
    ]);

    // Verify associations
    expect($companyEmail->related)->toBeInstanceOf(Company::class);
    expect($companyEmail->related->id)->toBe($company->id);

    expect($personEmail->related)->toBeInstanceOf(People::class);
    expect($personEmail->related->id)->toBe($person->id);

    expect($leadEmail->related)->toBeInstanceOf(Lead::class);
    expect($leadEmail->related->id)->toBe($lead->id);

    expect($opportunityEmail->related)->toBeInstanceOf(Opportunity::class);
    expect($opportunityEmail->related->id)->toBe($opportunity->id);
});

test('email threads maintain association integrity', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $threadId = fake()->uuid();

    // Create initial email
    $initialEmail = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Initial email',
        'thread_id' => $threadId,
        'related_id' => $company->id,
        'related_type' => Company::class,
        'status' => 'sent',
        'sent_at' => now()->subHour(),
    ]);

    // Create reply email in same thread
    $replyEmail = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Re: Initial email',
        'thread_id' => $threadId,
        'related_id' => $company->id,
        'related_type' => Company::class,
        'status' => 'sent',
        'sent_at' => now()->subMinutes(30),
    ]);

    // Create follow-up email in same thread
    $followUpEmail = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Re: Initial email - Follow up',
        'thread_id' => $threadId,
        'related_id' => $company->id,
        'related_type' => Company::class,
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // Verify thread integrity
    $threadEmails = EmailMessage::where('thread_id', $threadId)
        ->orderBy('sent_at')
        ->get();

    expect($threadEmails)->toHaveCount(3);

    // Verify all emails in thread are associated with same record
    foreach ($threadEmails as $email) {
        expect($email->related_id)->toBe($company->id);
        expect($email->related_type)->toBe(Company::class);
        expect($email->thread_id)->toBe($threadId);
    }

    // Verify chronological order
    expect($threadEmails[0]->id)->toBe($initialEmail->id);
    expect($threadEmails[1]->id)->toBe($replyEmail->id);
    expect($threadEmails[2]->id)->toBe($followUpEmail->id);
});

test('emails can be reassociated with different records', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $person = People::factory()->create(['team_id' => $this->team->id]);

    // Create email initially associated with company
    $email = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Email to reassociate',
        'related_id' => $company->id,
        'related_type' => Company::class,
    ]);

    // Verify initial association
    expect($email->related)->toBeInstanceOf(Company::class);
    expect($email->related->id)->toBe($company->id);

    // Reassociate with person
    $email->update([
        'related_id' => $person->id,
        'related_type' => People::class,
    ]);

    // Verify new association
    $email->refresh();
    expect($email->related)->toBeInstanceOf(People::class);
    expect($email->related->id)->toBe($person->id);
});

test('bulk email operations maintain record associations', function (): void {
    $company = Company::factory()->create(['team_id' => $this->team->id]);

    // Create multiple emails associated with the same company
    $emails = EmailMessage::factory()->count(5)->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'related_id' => $company->id,
        'related_type' => Company::class,
        'status' => 'draft',
    ]);

    // Bulk update status
    EmailMessage::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->update(['status' => 'sent', 'sent_at' => now()]);

    // Verify all emails are updated and associations maintained
    $updatedEmails = EmailMessage::where('related_id', $company->id)
        ->where('related_type', Company::class)
        ->get();

    expect($updatedEmails)->toHaveCount(5);

    foreach ($updatedEmails as $email) {
        expect($email->status)->toBe('sent');
        expect($email->sent_at)->not->toBeNull();
        expect($email->related_id)->toBe($company->id);
        expect($email->related_type)->toBe(Company::class);
    }
});

test('email search respects record associations', function (): void {
    $company1 = Company::factory()->create(['team_id' => $this->team->id, 'name' => 'Company One']);
    $company2 = Company::factory()->create(['team_id' => $this->team->id, 'name' => 'Company Two']);

    // Create emails for different companies
    $email1 = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Email for Company One',
        'related_id' => $company1->id,
        'related_type' => Company::class,
    ]);

    $email2 = EmailMessage::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'subject' => 'Email for Company Two',
        'related_id' => $company2->id,
        'related_type' => Company::class,
    ]);

    // Search emails by company association
    $company1Emails = EmailMessage::where('related_id', $company1->id)
        ->where('related_type', Company::class)
        ->get();

    $company2Emails = EmailMessage::where('related_id', $company2->id)
        ->where('related_type', Company::class)
        ->get();

    // Verify search results
    expect($company1Emails)->toHaveCount(1);
    expect($company1Emails->first()->id)->toBe($email1->id);

    expect($company2Emails)->toHaveCount(1);
    expect($company2Emails->first()->id)->toBe($email2->id);

    // Verify cross-contamination doesn't occur
    expect($company1Emails->contains('id', $email2->id))->toBeFalse();
    expect($company2Emails->contains('id', $email1->id))->toBeFalse();
});
