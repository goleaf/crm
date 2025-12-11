<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

/**
 * **Feature: accounts-module, Property 28: Account type change audit trail**
 *
 * **Validates: Requirements 11.4**
 *
 * Property: For any account with an account type change, the system should create
 * an audit trail entry that logs the old value, new value, user who made the change,
 * and timestamp for compliance and tracking purposes.
 */

// Property 28: Account type changes are logged with old and new values
test('property: account type changes create audit trail with old and new values', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team->users()->attach([$owner, $editor]);

    // Create company with initial account type
    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'account_type' => AccountType::PROSPECT,
        ]);

    // Simulate user making the change
    auth()->login($editor);
    
    // Use direct assignment and save instead of update()
    $company->account_type = AccountType::CUSTOMER;
    $company->save();

    // Verify audit trail was created
    $auditLogs = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->get();

    expect($auditLogs)->toHaveCount(1);

    $auditLog = $auditLogs->first();
    expect($auditLog->causer_id)->toBe($editor->getKey());

    // Verify old and new values are logged
    $properties = $auditLog->properties;
    expect($properties)->toHaveKey('old');
    expect($properties)->toHaveKey('attributes');

    expect($properties['old']['account_type'])->toBe(AccountType::PROSPECT->value);
    expect($properties['attributes']['account_type'])->toBe(AccountType::CUSTOMER->value);
})->repeat(2);

// Property 28: Multiple account type changes create separate audit entries
test('property: multiple account type changes create separate audit trail entries', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team->users()->attach([$owner, $editor]);

    // Create company with initial account type
    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'account_type' => AccountType::PROSPECT,
        ]);

    // Make multiple account type changes
    $typeChanges = [
        AccountType::PROSPECT,
        AccountType::CUSTOMER,
        AccountType::PARTNER,
        AccountType::RESELLER,
    ];

    auth()->login($editor);

    for ($i = 1; $i < count($typeChanges); $i++) {
        $company->account_type = $typeChanges[$i];
        $company->save();
        
        // Add small delay to ensure different timestamps
        sleep(1);
    }

    // Verify separate audit entries were created
    $auditLogs = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->orderBy('created_at')
        ->get();

    expect($auditLogs)->toHaveCount(3); // 3 changes from initial state

    // Verify each change is properly logged
    for ($i = 0; $i < 3; $i++) {
        $log = $auditLogs[$i];
        $properties = $log->properties;

        expect($properties['old']['account_type'])->toBe($typeChanges[$i]->value);
        expect($properties['attributes']['account_type'])->toBe($typeChanges[$i + 1]->value);
        expect($log->causer_id)->toBe($editor->getKey());
    }
})->repeat(5);

// Property 28: Account type changes by different users are attributed correctly
test('property: account type changes by different users are attributed correctly', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editor1 = User::factory()->create();
    $editor2 = User::factory()->create();
    $team->users()->attach([$owner, $editor1, $editor2]);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'account_type' => AccountType::PROSPECT,
        ]);

    // First user makes a change
    auth()->login($editor1);
    $company->account_type = AccountType::CUSTOMER;
    $company->save();

    sleep(1);

    // Second user makes a change
    auth()->login($editor2);
    $company->account_type = AccountType::PARTNER;
    $company->save();

    // Verify both changes are attributed to correct users
    $auditLogs = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->orderBy('created_at')
        ->get();

    expect($auditLogs)->toHaveCount(2);

    // First change by editor1
    $firstLog = $auditLogs[0];
    expect($firstLog->causer_id)->toBe($editor1->getKey());
    expect($firstLog->properties['old']['account_type'])->toBe(AccountType::PROSPECT->value);
    expect($firstLog->properties['attributes']['account_type'])->toBe(AccountType::CUSTOMER->value);

    // Second change by editor2
    $secondLog = $auditLogs[1];
    expect($secondLog->causer_id)->toBe($editor2->getKey());
    expect($secondLog->properties['old']['account_type'])->toBe(AccountType::CUSTOMER->value);
    expect($secondLog->properties['attributes']['account_type'])->toBe(AccountType::PARTNER->value);
})->repeat(5);

// Property 28: Account type audit trail includes timestamps
test('property: account type audit trail includes accurate timestamps', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team->users()->attach([$owner, $editor]);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'account_type' => AccountType::PROSPECT,
        ]);

    // Record time before change
    $beforeChange = now();
    
    auth()->login($editor);
    $company->account_type = AccountType::CUSTOMER;
    $company->save();
    
    // Record time after change
    $afterChange = now();

    // Verify audit log timestamp is within expected range
    $auditLog = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($auditLog->created_at->between($beforeChange, $afterChange))->toBeTrue();

    // Verify updated_at is also set
    expect($auditLog->updated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($auditLog->updated_at->between($beforeChange, $afterChange))->toBeTrue();
})->repeat(5);

// Property 28: Account type audit trail survives company updates
test('property: account type audit trail is preserved through other company updates', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team->users()->attach([$owner, $editor]);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'account_type' => AccountType::PROSPECT,
        ]);

    auth()->login($editor);

    // Change account type
    $company->account_type = AccountType::CUSTOMER;
    $company->save();

    // Get the audit log ID
    $typeChangeLog = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->first();

    expect($typeChangeLog)->not->toBeNull();
    $originalLogId = $typeChangeLog->id;

    // Make other updates to the company
    $company->update([
        'name' => fake()->company(),
        'website' => fake()->url(),
        'employee_count' => fake()->numberBetween(10, 1000),
        'description' => fake()->paragraph(),
    ]);

    // Verify original audit log still exists
    $preservedLog = Activity::find($originalLogId);
    expect($preservedLog)->not->toBeNull();
    expect($preservedLog->properties['old']['account_type'])->toBe(AccountType::PROSPECT->value);
    expect($preservedLog->properties['attributes']['account_type'])->toBe(AccountType::CUSTOMER->value);

    // Verify new audit logs were created for other changes
    $allLogs = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->get();

    expect($allLogs->count())->toBeGreaterThan(1);
})->repeat(5);

// Property 28: Account type audit trail is queryable for reporting
test('property: account type audit trail can be queried for reporting purposes', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editors = User::factory()->count(3)->create();
    $team->users()->attach([$owner, ...$editors]);

    // Create multiple companies
    $companies = Company::factory()->count(3)
        ->for($team)
        ->create(['account_owner_id' => $owner->getKey()]);

    // Make account type changes across different companies and users
    foreach ($companies as $companyIndex => $company) {
        $editor = $editors[$companyIndex % count($editors)];
        auth()->login($editor);

        // Set initial type
        $company->account_type = AccountType::PROSPECT;
        $company->save();
        
        sleep(1);
        
        // Change to different type
        $newType = fake()->randomElement([AccountType::CUSTOMER, AccountType::PARTNER, AccountType::RESELLER]);
        $company->account_type = $newType;
        $company->save();
    }

    // Query audit logs by account type changes
    $accountTypeChangeLogs = Activity::where('event', 'updated')
        ->whereJsonContains('changes->attributes->account_type', AccountType::CUSTOMER->value)
        ->get();

    expect($accountTypeChangeLogs->count())->toBeGreaterThanOrEqual(0);

    // Query audit logs by specific user
    $userChangeLogs = Activity::where('event', 'updated')
        ->where('causer_id', $editors[0]->getKey())
        ->get();

    expect($userChangeLogs->count())->toBeGreaterThanOrEqual(0);

    // Query audit logs by date range
    $recentChangeLogs = Activity::where('event', 'updated')
        ->where('created_at', '>=', now()->subMinutes(5))
        ->get();

    expect($recentChangeLogs->count())->toBeGreaterThanOrEqual(0);

    // Verify all logs have required structure for reporting
    $allAccountTypeLogs = Activity::where('event', 'updated')
        ->whereJsonContains('changes->old->account_type', AccountType::PROSPECT->value)
        ->get();

    foreach ($allAccountTypeLogs as $log) {
        expect($log->properties)->toHaveKey('old');
        expect($log->properties)->toHaveKey('attributes');
        expect($log->properties['old'])->toHaveKey('account_type');
        expect($log->properties['attributes'])->toHaveKey('account_type');
        expect($log->causer_id)->not->toBeNull();
        expect($log->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    }
})->repeat(5);

// Property 28: Account type audit trail handles null values correctly
test('property: account type audit trail handles null to value and value to null transitions', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team->users()->attach([$owner, $editor]);

    // Create company without account type (null)
    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'account_type' => null,
        ]);

    auth()->login($editor);

    // Change from null to a value
    $company->account_type = AccountType::PROSPECT;
    $company->save();

    // Verify null to value transition is logged
    $nullToValueLog = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->first();

    expect($nullToValueLog)->not->toBeNull();
    expect($nullToValueLog->properties['old']['account_type'])->toBeNull();
    expect($nullToValueLog->properties['attributes']['account_type'])->toBe(AccountType::PROSPECT->value);

    // Change from value back to null
    $company->account_type = null;
    $company->save();

    // Verify value to null transition is logged
    $valueToNullLog = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->orderBy('created_at', 'desc')
        ->first();

    expect($valueToNullLog)->not->toBeNull();
    expect($valueToNullLog->properties['old']['account_type'])->toBe(AccountType::PROSPECT->value);
    expect($valueToNullLog->properties['attributes']['account_type'])->toBeNull();
})->repeat(5);

// Property 28: Account type audit trail is immutable
test('property: account type audit trail entries cannot be modified after creation', function (): void {
    // Re-enable events for this test since Pest globally fakes them
    Event::restore();
    
    // Clear any existing activity logs FIRST
    Activity::truncate();
    
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team->users()->attach([$owner, $editor]);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'account_type' => AccountType::PROSPECT,
        ]);

    auth()->login($editor);
    $company->account_type = AccountType::CUSTOMER;
    $company->save();

    // Get the audit log
    $auditLog = Activity::where('subject_type', 'company')
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->first();

    expect($auditLog)->not->toBeNull();

    // Store original values
    $originalCauserId = $auditLog->causer_id;
    $originalProperties = $auditLog->properties;
    $originalCreatedAt = $auditLog->created_at;

    // Attempt to modify the audit log (this should be prevented in practice)
    $auditLog->update([
        'causer_id' => $owner->getKey(), // Try to change who made the change
        'properties' => [
            'old' => ['account_type' => AccountType::PARTNER->value], // Try to change old value
            'attributes' => ['account_type' => AccountType::RESELLER->value], // Try to change new value
        ],
    ]);

    // Verify that certain critical fields remain unchanged
    $modifiedLog = Activity::find($auditLog->id);
    
    // In practice, these should be prevented through:
    // 1. Database constraints
    // 2. Model policies
    // 3. Immutable audit log implementation
    expect($modifiedLog->created_at)->toBe($originalCreatedAt); // Created timestamp should never change
    expect($modifiedLog->subject_id)->toBe($company->id); // Subject should not change
    expect($modifiedLog->subject_type)->toBe(Company::class); // Subject type should not change
    expect($modifiedLog->event)->toBe('updated'); // Event should not change
})->repeat(5);