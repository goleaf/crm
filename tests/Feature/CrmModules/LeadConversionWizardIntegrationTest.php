<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use App\Services\LeadConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->switchTeam($this->team);
    $this->actingAs($this->user);
});

/**
 * Integration test for lead conversion wizard.
 *
 * **Validates: Requirements 3.5**
 *
 * Tests the complete lead conversion flow including UI interactions,
 * data validation, and atomic operations.
 */
test('conversion wizard creates account, contact, and opportunity atomically', function (): void {
    Event::fake();

    // Create a qualified lead
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'qualified',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+1-555-0123',
        'company' => 'Acme Corp',
        'job_title' => 'CEO',
    ]);

    // Conversion wizard data
    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => [
            'name' => 'Acme Corporation',
            'account_type' => 'prospect',
            'industry' => 'Technology',
            'website' => 'https://acme.com',
            'phone' => '+1-555-0100',
        ],
        'create_contact' => true,
        'contact_data' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@acme.com',
            'phone' => '+1-555-0123',
            'job_title' => 'Chief Executive Officer',
        ],
        'create_opportunity' => true,
        'opportunity_data' => [
            'name' => 'Acme Corp - Initial Deal',
            'stage' => 'prospecting',
            'amount' => 50000,
            'probability' => 25,
            'expected_close_date' => now()->addDays(30)->format('Y-m-d'),
        ],
    ];

    // Execute conversion through API
    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'account_id',
            'contact_id',
            'opportunity_id',
        ],
    ]);

    $responseData = $response->json('data');

    // Verify account was created
    $account = Account::find($responseData['account_id']);
    expect($account)->not->toBeNull();
    expect($account->name)->toBe($conversionData['account_data']['name']);
    expect($account->account_type)->toBe($conversionData['account_data']['account_type']);
    expect($account->industry)->toBe($conversionData['account_data']['industry']);
    expect($account->team_id)->toBe($this->team->id);

    // Verify contact was created
    $contact = People::find($responseData['contact_id']);
    expect($contact)->not->toBeNull();
    expect($contact->first_name)->toBe($conversionData['contact_data']['first_name']);
    expect($contact->last_name)->toBe($conversionData['contact_data']['last_name']);
    expect($contact->email)->toBe($conversionData['contact_data']['email']);
    expect($contact->company_id)->toBe($account->id);
    expect($contact->team_id)->toBe($this->team->id);

    // Verify opportunity was created
    $opportunity = Opportunity::find($responseData['opportunity_id']);
    expect($opportunity)->not->toBeNull();
    expect($opportunity->name)->toBe($conversionData['opportunity_data']['name']);
    expect($opportunity->stage)->toBe($conversionData['opportunity_data']['stage']);
    expect($opportunity->amount)->toBe($conversionData['opportunity_data']['amount']);
    expect($opportunity->probability)->toBe($conversionData['opportunity_data']['probability']);
    expect($opportunity->account_id)->toBe($account->id);
    expect($opportunity->contact_id)->toBe($contact->id);
    expect($opportunity->team_id)->toBe($this->team->id);

    // Verify lead was marked as converted
    $convertedLead = Lead::find($lead->id);
    expect($convertedLead->status)->toBe('converted');
    expect($convertedLead->converted_at)->not->toBeNull();
    expect($convertedLead->converted_account_id)->toBe($account->id);
    expect($convertedLead->converted_contact_id)->toBe($contact->id);
    expect($convertedLead->converted_opportunity_id)->toBe($opportunity->id);

    // Verify relationships are bidirectional
    expect($account->contacts->pluck('id'))->toContain($contact->id);
    expect($account->opportunities->pluck('id'))->toContain($opportunity->id);
    expect($contact->opportunities->pluck('id'))->toContain($opportunity->id);
});

test('conversion wizard handles partial conversion (account and contact only)', function (): void {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'qualified',
    ]);

    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => [
            'name' => 'Test Company',
            'account_type' => 'prospect',
        ],
        'create_contact' => true,
        'contact_data' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@test.com',
        ],
        'create_opportunity' => false, // No opportunity
    ];

    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // Verify account and contact created
    expect($responseData['account_id'])->not->toBeNull();
    expect($responseData['contact_id'])->not->toBeNull();
    expect($responseData['opportunity_id'])->toBeNull();

    // Verify lead conversion tracking
    $convertedLead = Lead::find($lead->id);
    expect($convertedLead->status)->toBe('converted');
    expect($convertedLead->converted_account_id)->toBe($responseData['account_id']);
    expect($convertedLead->converted_contact_id)->toBe($responseData['contact_id']);
    expect($convertedLead->converted_opportunity_id)->toBeNull();
});

test('conversion wizard uses existing account when specified', function (): void {
    // Create existing account
    $existingAccount = Account::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'qualified',
    ]);

    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => false,
        'existing_account_id' => $existingAccount->id,
        'create_contact' => true,
        'contact_data' => [
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob@existing.com',
        ],
        'create_opportunity' => false,
    ];

    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // Should use existing account
    expect($responseData['account_id'])->toBe($existingAccount->id);

    // Contact should be linked to existing account
    $contact = People::find($responseData['contact_id']);
    expect($contact->company_id)->toBe($existingAccount->id);

    // Lead should reference existing account
    $convertedLead = Lead::find($lead->id);
    expect($convertedLead->converted_account_id)->toBe($existingAccount->id);
});

test('conversion wizard validates required fields', function (): void {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'qualified',
    ]);

    // Missing required account data
    $invalidData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => [
            // Missing name
            'account_type' => 'prospect',
        ],
        'create_contact' => false,
        'create_opportunity' => false,
    ];

    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $invalidData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['account_data.name']);

    // Lead should not be converted
    $lead->refresh();
    expect($lead->status)->not->toBe('converted');
});

test('conversion wizard prevents double conversion', function (): void {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'converted', // Already converted
        'converted_at' => now(),
    ]);

    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => ['name' => 'Test'],
        'create_contact' => false,
        'create_opportunity' => false,
    ];

    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Lead has already been converted',
    ]);
});

test('conversion wizard is atomic - rolls back on failure', function (): void {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'qualified',
    ]);

    // Force a database error by using invalid data that will fail after account creation
    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => [
            'name' => 'Test Account',
            'account_type' => 'prospect',
        ],
        'create_contact' => true,
        'contact_data' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'invalid-email-that-will-cause-constraint-violation-if-duplicate-exists',
        ],
        'create_opportunity' => true,
        'opportunity_data' => [
            'name' => 'Test Opp',
            'stage' => 'invalid_stage', // This should cause validation failure
        ],
    ];

    // Count records before conversion attempt
    $accountCountBefore = Account::count();
    $contactCountBefore = People::count();
    $opportunityCountBefore = Opportunity::count();

    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);

    $response->assertStatus(422);

    // Verify no records were created (atomic rollback)
    expect(Account::count())->toBe($accountCountBefore);
    expect(People::count())->toBe($contactCountBefore);
    expect(Opportunity::count())->toBe($opportunityCountBefore);

    // Lead should not be marked as converted
    $lead->refresh();
    expect($lead->status)->not->toBe('converted');
    expect($lead->converted_at)->toBeNull();
});

test('conversion wizard creates activity timeline entries', function (): void {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'qualified',
    ]);

    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => ['name' => 'Activity Test Corp'],
        'create_contact' => true,
        'contact_data' => [
            'first_name' => 'Activity',
            'last_name' => 'Tester',
            'email' => 'activity@test.com',
        ],
        'create_opportunity' => false,
    ];

    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);
    $response->assertStatus(200);

    $responseData = $response->json('data');

    // Verify conversion activity was logged on lead
    $lead->refresh();
    $conversionActivity = $lead->activities()
        ->where('description', 'like', '%converted%')
        ->first();
    expect($conversionActivity)->not->toBeNull();

    // Verify creation activities on new records
    $account = Account::find($responseData['account_id']);
    $accountActivity = $account->activities()
        ->where('description', 'like', '%created from lead conversion%')
        ->first();
    expect($accountActivity)->not->toBeNull();

    $contact = People::find($responseData['contact_id']);
    $contactActivity = $contact->activities()
        ->where('description', 'like', '%created from lead conversion%')
        ->first();
    expect($contactActivity)->not->toBeNull();
});

test('conversion wizard handles team isolation', function (): void {
    // Create lead in different team
    $otherTeam = Team::factory()->create();
    $lead = Lead::factory()->create([
        'team_id' => $otherTeam->id,
        'status' => 'qualified',
    ]);

    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => ['name' => 'Cross Team Test'],
        'create_contact' => false,
        'create_opportunity' => false,
    ];

    // Should not be able to convert lead from different team
    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);

    $response->assertStatus(404); // Lead not found in current team context
});

test('conversion wizard preserves lead source and campaign data', function (): void {
    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'status' => 'qualified',
        'source' => 'trade_show',
        'campaign' => 'summer_2024',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'brand_keywords',
    ]);

    $conversionData = [
        'lead_id' => $lead->id,
        'create_account' => true,
        'account_data' => ['name' => 'Source Test Corp'],
        'create_contact' => true,
        'contact_data' => [
            'first_name' => 'Source',
            'last_name' => 'Tester',
            'email' => 'source@test.com',
        ],
        'create_opportunity' => true,
        'opportunity_data' => [
            'name' => 'Source Test Deal',
            'stage' => 'prospecting',
            'amount' => 25000,
        ],
    ];

    $response = $this->postJson('/api/leads/' . $lead->id . '/convert', $conversionData);
    $response->assertStatus(200);

    $responseData = $response->json('data');

    // Verify source data is preserved on contact
    $contact = People::find($responseData['contact_id']);
    expect($contact->lead_source)->toBe($lead->source);
    expect($contact->campaign)->toBe($lead->campaign);

    // Verify source data is preserved on opportunity
    $opportunity = Opportunity::find($responseData['opportunity_id']);
    expect($opportunity->lead_source)->toBe($lead->source);
    expect($opportunity->campaign)->toBe($lead->campaign);
});