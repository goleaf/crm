<?php

declare(strict_types=1);

use App\Models\SupportCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
// Uses TestCase and RefreshDatabase globally via Pest.php

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->switchTeam($this->team);
    $this->actingAs($this->user);
});

/**
 * Integration test for email-to-case ingestion pipeline.
 *
 * **Validates: Requirements 5.2, 5.3**
 *
 * Tests the complete flow from email receipt to case creation,
 * assignment, and initial processing.
 */
test('email-to-case creates case from incoming email', function (): void {
    Event::fake();
    Mail::fake();

    // Simulate incoming email data
    $emailData = [
        'from' => 'customer@example.com',
        'from_name' => 'John Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'Unable to login to my account',
        'body' => 'Hi, I am having trouble logging into my account. I keep getting an error message that says my password is incorrect, but I am sure it is right. Can you please help?',
        'html_body' => '<p>Hi, I am having trouble logging into my account...</p>',
        'message_id' => '<20240101120000.12345@example.com>',
        'in_reply_to' => null,
        'references' => null,
        'date' => now()->toISOString(),
        'attachments' => [],
        'headers' => [
            'X-Priority' => '3',
            'X-Mailer' => 'Outlook',
        ],
    ];

    // Process email through email-to-case service
    $response = $this->postJson('/api/email-to-case/inbound', $emailData);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'success',
        'message',
        'case_id',
    ]);

    // Verify case was created
    $caseId = $response->json('case_id');
    $case = SupportCase::find($caseId);

    expect($case)->not->toBeNull();
    expect($case->subject)->toBe($emailData['subject']);
    expect($case->description)->toBe($emailData['body']);
    expect($case->customer_email)->toBe($emailData['from']);
    expect($case->customer_name)->toBe($emailData['from_name']);
    expect($case->channel)->toBe('email');
    expect($case->status)->toBe('new');
    expect($case->priority)->not->toBeNull();
    expect($case->team_id)->toBe($this->team->id);

    // Verify email threading information
    expect($case->email_message_id)->toBe($emailData['message_id']);
    expect($case->email_thread_id)->not->toBeNull();

    // Verify case was assigned to a queue
    expect($case->queue)->not->toBeNull();

    // Verify case was assigned to a user
    expect($case->assigned_user_id)->not->toBeNull();
    expect($case->assigned_at)->not->toBeNull();
});

test('email-to-case handles email threading correctly', function (): void {
    // Create initial case from first email
    $initialEmailData = [
        'from' => 'customer@example.com',
        'from_name' => 'John Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'Login Issue',
        'body' => 'I cannot login to my account.',
        'message_id' => '<initial@example.com>',
        'in_reply_to' => null,
        'references' => null,
        'date' => now()->toISOString(),
    ];

    $response1 = $this->postJson('/api/email-to-case/inbound', $initialEmailData);
    $response1->assertStatus(201);
    $initialCaseId = $response1->json('case_id');
    $initialCase = SupportCase::find($initialCaseId);

    // Send reply email
    $replyEmailData = [
        'from' => 'customer@example.com',
        'from_name' => 'John Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'Re: Login Issue',
        'body' => 'I tried resetting my password but still cannot login.',
        'message_id' => '<reply@example.com>',
        'in_reply_to' => '<initial@example.com>',
        'references' => '<initial@example.com>',
        'date' => now()->addMinutes(30)->toISOString(),
    ];

    $response2 = $this->postJson('/api/email-to-case/inbound', $replyEmailData);
    $response2->assertStatus(200); // Should update existing case, not create new one

    // Verify reply was added to existing case
    $updatedCase = SupportCase::find($initialCaseId);
    expect($updatedCase->email_thread_id)->toBe($initialCase->email_thread_id);

    // Verify case has multiple email messages
    expect($updatedCase->emailMessages)->toHaveCount(2);

    // Verify latest message content
    $latestMessage = $updatedCase->emailMessages()->latest()->first();
    expect($latestMessage->body)->toBe($replyEmailData['body']);
    expect($latestMessage->message_id)->toBe($replyEmailData['message_id']);
});

test('email-to-case assigns cases to correct queue based on rules', function (): void {
    // Email with high priority indicators
    $urgentEmailData = [
        'from' => 'vip@example.com',
        'from_name' => 'VIP Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'URGENT: System Down',
        'body' => 'Our entire system is down and we cannot process orders!',
        'message_id' => '<urgent@example.com>',
        'date' => now()->toISOString(),
        'headers' => [
            'X-Priority' => '1', // High priority
        ],
    ];

    $response = $this->postJson('/api/email-to-case/inbound', $urgentEmailData);
    $response->assertStatus(201);

    $case = SupportCase::find($response->json('case_id'));

    // Should be assigned to high priority queue
    expect($case->priority)->toBe('high');
    expect($case->queue)->toBe('critical');
});

test('email-to-case handles attachments', function (): void {
    $emailWithAttachments = [
        'from' => 'customer@example.com',
        'from_name' => 'John Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'Error Screenshot',
        'body' => 'Please see attached screenshot of the error.',
        'message_id' => '<attachment@example.com>',
        'date' => now()->toISOString(),
        'attachments' => [
            [
                'filename' => 'error_screenshot.png',
                'content_type' => 'image/png',
                'size' => 1024000,
                'content' => base64_encode('fake-image-content'),
            ],
            [
                'filename' => 'error_log.txt',
                'content_type' => 'text/plain',
                'size' => 2048,
                'content' => base64_encode('Error log content here'),
            ],
        ],
    ];

    $response = $this->postJson('/api/email-to-case/inbound', $emailWithAttachments);
    $response->assertStatus(201);

    $case = SupportCase::find($response->json('case_id'));

    // Verify attachments were processed
    expect($case->attachments)->toHaveCount(2);

    $attachments = $case->attachments;
    expect($attachments->pluck('filename'))->toContain('error_screenshot.png');
    expect($attachments->pluck('filename'))->toContain('error_log.txt');
});

test('email-to-case validates email format', function (): void {
    // Invalid email data
    $invalidEmailData = [
        'from' => 'invalid-email', // Invalid email format
        'to' => 'support@ourcompany.com',
        'subject' => '', // Empty subject
        'body' => '', // Empty body
        'message_id' => '', // Empty message ID
    ];

    $response = $this->postJson('/api/email-to-case/inbound', $invalidEmailData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['from', 'subject', 'body', 'message_id']);

    // No case should be created
    expect(SupportCase::count())->toBe(0);
});

test('email-to-case handles spam and malicious content', function (): void {
    // Email with spam indicators
    $spamEmailData = [
        'from' => 'spam@suspicious.com',
        'from_name' => 'Spam Sender',
        'to' => 'support@ourcompany.com',
        'subject' => 'URGENT!!! CLICK HERE FOR MONEY!!!',
        'body' => 'Click this link to claim your prize: http://malicious-site.com/virus',
        'message_id' => '<spam@suspicious.com>',
        'date' => now()->toISOString(),
        'headers' => [
            'X-Spam-Score' => '9.5',
            'X-Spam-Flag' => 'YES',
        ],
    ];

    $response = $this->postJson('/api/email-to-case/inbound', $spamEmailData);

    // Should either reject or quarantine
    if ($response->status() === 422) {
        // Rejected as spam
        expect(SupportCase::count())->toBe(0);
    } else {
        // Quarantined
        $response->assertStatus(201);
        $case = SupportCase::find($response->json('case_id'));
        expect($case->status)->toBe('quarantined');
    }
});

test('email-to-case creates SLA timers', function (): void {
    $emailData = [
        'from' => 'customer@example.com',
        'from_name' => 'John Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'Need Help',
        'body' => 'I need assistance with my account.',
        'message_id' => '<sla@example.com>',
        'date' => now()->toISOString(),
    ];

    $response = $this->postJson('/api/email-to-case/inbound', $emailData);
    $response->assertStatus(201);

    $case = SupportCase::find($response->json('case_id'));

    // Verify SLA timers are set
    expect($case->sla_first_response_due)->not->toBeNull();
    expect($case->sla_resolution_due)->not->toBeNull();
    expect($case->sla_first_response_due->isFuture())->toBeTrue();
    expect($case->sla_resolution_due->isFuture())->toBeTrue();
});

test('email-to-case handles duplicate message IDs', function (): void {
    $emailData = [
        'from' => 'customer@example.com',
        'from_name' => 'John Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'Duplicate Test',
        'body' => 'This is a test message.',
        'message_id' => '<duplicate@example.com>',
        'date' => now()->toISOString(),
    ];

    // Send first email
    $response1 = $this->postJson('/api/email-to-case/inbound', $emailData);
    $response1->assertStatus(201);
    $firstCaseId = $response1->json('case_id');

    // Send same email again (duplicate message ID)
    $response2 = $this->postJson('/api/email-to-case/inbound', $emailData);

    // Should not create duplicate case
    $response2->assertStatus(200);
    expect($response2->json('case_id'))->toBe($firstCaseId);

    // Verify only one case exists
    expect(SupportCase::count())->toBe(1);
});

test('email-to-case handles auto-replies and out-of-office messages', function (): void {
    $autoReplyData = [
        'from' => 'noreply@example.com',
        'from_name' => 'Auto Reply',
        'to' => 'support@ourcompany.com',
        'subject' => 'Auto-Reply: Out of Office',
        'body' => 'This is an automated response. I am currently out of office.',
        'message_id' => '<autoreply@example.com>',
        'date' => now()->toISOString(),
        'headers' => [
            'Auto-Submitted' => 'auto-reply',
            'X-Autoreply' => 'yes',
        ],
    ];

    $response = $this->postJson('/api/email-to-case/inbound', $autoReplyData);

    // Should not create case for auto-replies
    $response->assertStatus(200);
    $response->assertJson(['message' => 'Auto-reply ignored']);

    expect(SupportCase::count())->toBe(0);
});

test('email-to-case creates audit trail', function (): void {
    $emailData = [
        'from' => 'customer@example.com',
        'from_name' => 'John Customer',
        'to' => 'support@ourcompany.com',
        'subject' => 'Audit Test',
        'body' => 'This is for audit testing.',
        'message_id' => '<audit@example.com>',
        'date' => now()->toISOString(),
    ];

    $response = $this->postJson('/api/email-to-case/inbound', $emailData);
    $response->assertStatus(201);

    $case = SupportCase::find($response->json('case_id'));

    // Verify audit activities were created
    expect($case->activities)->not->toBeEmpty();

    $creationActivity = $case->activities->where('description', 'Case created from email')->first();
    expect($creationActivity)->not->toBeNull();

    if ($case->assigned_user_id) {
        $assignmentActivity = $case->activities->where('description', 'like', '%assigned%')->first();
        expect($assignmentActivity)->not->toBeNull();
    }
});

test('email-to-case handles high volume email processing', function (): void {
    // Process multiple emails rapidly
    $emailCount = 10;
    $responses = [];

    for ($i = 0; $i < $emailCount; $i++) {
        $emailData = [
            'from' => "customer{$i}@example.com",
            'from_name' => "Customer {$i}",
            'to' => 'support@ourcompany.com',
            'subject' => "Issue #{$i}",
            'body' => "This is issue number {$i}.",
            'message_id' => "<issue{$i}@example.com>",
            'date' => now()->addSeconds($i)->toISOString(),
        ];

        $response = $this->postJson('/api/email-to-case/inbound', $emailData);
        $responses[] = $response;
    }

    // All emails should be processed successfully
    foreach ($responses as $response) {
        $response->assertStatus(201);
    }

    // Verify all cases were created
    expect(SupportCase::count())->toBe($emailCount);

    // Verify all cases were assigned
    $unassignedCases = SupportCase::whereNull('assigned_user_id')->count();
    expect($unassignedCases)->toBe(0);
});
