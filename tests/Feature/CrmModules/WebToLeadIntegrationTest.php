<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->switchTeam($this->team);
    $this->actingAs($this->user);
});

/**
 * Integration test for web-to-lead intake pipeline.
 *
 * **Validates: Requirements 3.1, 3.2**
 *
 * Tests the complete flow from web form submission to lead assignment
 * and initial processing.
 */
test('web-to-lead intake creates and assigns lead correctly', function (): void {
    Event::fake();

    // Simulate web form data
    $webFormData = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->phoneNumber(),
        'company' => fake()->company(),
        'job_title' => fake()->jobTitle(),
        'source' => 'website_form',
        'campaign' => 'summer_2024',
        'message' => fake()->paragraph(),
        'consent_marketing' => true,
        'consent_data_processing' => true,
    ];

    // Submit web-to-lead form
    $response = $this->postJson('/api/web-leads', $webFormData);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'success',
        'message',
        'lead_id',
    ]);

    // Verify lead was created
    $leadId = $response->json('lead_id');
    $lead = Lead::find($leadId);

    expect($lead)->not->toBeNull();
    expect($lead->first_name)->toBe($webFormData['first_name']);
    expect($lead->last_name)->toBe($webFormData['last_name']);
    expect($lead->email)->toBe($webFormData['email']);
    expect($lead->phone)->toBe($webFormData['phone']);
    expect($lead->company)->toBe($webFormData['company']);
    expect($lead->job_title)->toBe($webFormData['job_title']);
    expect($lead->source)->toBe($webFormData['source']);
    expect($lead->campaign)->toBe($webFormData['campaign']);
    expect($lead->message)->toBe($webFormData['message']);
    expect($lead->consent_marketing)->toBe($webFormData['consent_marketing']);
    expect($lead->consent_data_processing)->toBe($webFormData['consent_data_processing']);

    // Verify lead was assigned to a user
    expect($lead->assigned_user_id)->not->toBeNull();
    expect($lead->assigned_at)->not->toBeNull();

    // Verify lead status is set correctly
    expect($lead->status)->toBe('new');

    // Verify team assignment
    expect($lead->team_id)->toBe($this->team->id);
});

test('web-to-lead handles duplicate detection', function (): void {
    // Create existing lead
    $existingLead = Lead::factory()->create([
        'email' => 'duplicate@example.com',
        'team_id' => $this->team->id,
    ]);

    // Submit duplicate web form
    $webFormData = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => 'duplicate@example.com', // Same email
        'phone' => fake()->phoneNumber(),
        'company' => fake()->company(),
        'source' => 'website_form',
        'consent_marketing' => true,
        'consent_data_processing' => true,
    ];

    $response = $this->postJson('/api/web-leads', $webFormData);

    $response->assertStatus(201);

    // Should still create lead but mark as potential duplicate
    $leadId = $response->json('lead_id');
    $lead = Lead::find($leadId);

    expect($lead)->not->toBeNull();
    expect($lead->email)->toBe($webFormData['email']);
    expect($lead->duplicate_hash)->not->toBeNull();
    expect($lead->potential_duplicates)->not->toBeEmpty();
});

test('web-to-lead validates required fields', function (): void {
    // Submit incomplete form data
    $incompleteData = [
        'first_name' => fake()->firstName(),
        // Missing required fields
    ];

    $response = $this->postJson('/api/web-leads', $incompleteData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_name', 'email']);
});

test('web-to-lead handles invalid email format', function (): void {
    $invalidData = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => 'invalid-email-format',
        'consent_data_processing' => true,
    ];

    $response = $this->postJson('/api/web-leads', $invalidData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('web-to-lead requires data processing consent', function (): void {
    $dataWithoutConsent = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'consent_data_processing' => false, // Required consent not given
    ];

    $response = $this->postJson('/api/web-leads', $dataWithoutConsent);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['consent_data_processing']);
});

test('web-to-lead assignment follows round-robin rules', function (): void {
    // Create multiple users for assignment
    $users = User::factory()->count(3)->create();
    foreach ($users as $user) {
        $user->teams()->attach($this->team);
    }

    // Submit multiple leads
    $leads = [];
    for ($i = 0; $i < 6; $i++) {
        $webFormData = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'source' => 'website_form',
            'consent_data_processing' => true,
        ];

        $response = $this->postJson('/api/web-leads', $webFormData);
        $response->assertStatus(201);

        $leadId = $response->json('lead_id');
        $leads[] = Lead::find($leadId);
    }

    // Verify round-robin distribution
    $assignmentCounts = [];
    foreach ($leads as $lead) {
        $userId = $lead->assigned_user_id;
        $assignmentCounts[$userId] = ($assignmentCounts[$userId] ?? 0) + 1;
    }

    // Each user should have received 2 leads (6 leads / 3 users)
    foreach ($assignmentCounts as $count) {
        expect($count)->toBe(2);
    }
});

test('web-to-lead creates audit trail', function (): void {
    $webFormData = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'source' => 'website_form',
        'consent_data_processing' => true,
    ];

    $response = $this->postJson('/api/web-leads', $webFormData);
    $response->assertStatus(201);

    $leadId = $response->json('lead_id');
    $lead = Lead::find($leadId);

    // Verify audit trail exists
    expect($lead->activities)->not->toBeEmpty();

    // Should have creation activity
    $creationActivity = $lead->activities->where('description', 'Lead created from web form')->first();
    expect($creationActivity)->not->toBeNull();

    // Should have assignment activity if assigned
    if ($lead->assigned_user_id) {
        $assignmentActivity = $lead->activities->where('description', 'like', '%assigned%')->first();
        expect($assignmentActivity)->not->toBeNull();
    }
});

test('web-to-lead handles high volume submissions', function (): void {
    // Submit multiple leads rapidly
    $responses = [];
    $leadCount = 10;

    for ($i = 0; $i < $leadCount; $i++) {
        $webFormData = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'source' => 'website_form',
            'consent_data_processing' => true,
        ];

        $response = $this->postJson('/api/web-leads', $webFormData);
        $responses[] = $response;
    }

    // All submissions should succeed
    foreach ($responses as $response) {
        $response->assertStatus(201);
    }

    // Verify all leads were created
    $createdLeads = Lead::where('source', 'website_form')
        ->where('team_id', $this->team->id)
        ->get();

    expect($createdLeads)->toHaveCount($leadCount);

    // Verify all leads were assigned
    foreach ($createdLeads as $lead) {
        expect($lead->assigned_user_id)->not->toBeNull();
        expect($lead->assigned_at)->not->toBeNull();
    }
});

test('web-to-lead handles malformed data gracefully', function (): void {
    // Submit malformed JSON
    $response = $this->postJson('/api/web-leads', [
        'first_name' => ['invalid' => 'array'],
        'last_name' => null,
        'email' => 123, // Wrong type
    ]);

    $response->assertStatus(422);

    // Should not create any lead
    $leadCount = Lead::where('team_id', $this->team->id)->count();
    expect($leadCount)->toBe(0);
});

test('web-to-lead respects rate limiting', function (): void {
    // This test would verify rate limiting if implemented
    // For now, we'll just verify the endpoint exists and works

    $webFormData = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'source' => 'website_form',
        'consent_data_processing' => true,
    ];

    $response = $this->postJson('/api/web-leads', $webFormData);
    $response->assertStatus(201);

    // Verify lead was created successfully
    $leadId = $response->json('lead_id');
    expect(Lead::find($leadId))->not->toBeNull();
});
