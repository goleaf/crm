<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\AccountsModule;

use App\Enums\AccountType;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Feature: accounts-module, Property 28: Account type change audit trail**
 *
 * **Validates: Requirements 11.4**
 *
 * Property: For any account with an account type change, the system should create
 * an audit trail entry that logs the old value, new value, user who made the change,
 * and timestamp for compliance and tracking purposes.
 *
 * NOTE: This test file does NOT use the global Pest configuration that fakes events,
 * so we can test the actual LogsActivity trait functionality.
 */
class AccountTypeAuditTrailRealEventsTest extends TestCase
{
    use RefreshDatabase;

    public function testAccountTypeChangesCreateAuditTrailWithOldAndNewValues(): void
    {
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
        $this->actingAs($editor);
        
        // Use direct assignment and save instead of update()
        $company->account_type = AccountType::CUSTOMER;
        $company->save();

        // Verify audit trail was created
        $auditLogs = Activity::where('subject_type', 'company')
            ->where('subject_id', $company->id)
            ->where('event', 'updated')
            ->get();

        $this->assertCount(1, $auditLogs);

        $auditLog = $auditLogs->first();
        $this->assertEquals($editor->getKey(), $auditLog->causer_id);

        // Verify old and new values are logged
        $properties = $auditLog->properties;
        $this->assertArrayHasKey('old', $properties);
        $this->assertArrayHasKey('attributes', $properties);

        $this->assertEquals(AccountType::PROSPECT->value, $properties['old']['account_type']);
        $this->assertEquals(AccountType::CUSTOMER->value, $properties['attributes']['account_type']);
    }

    public function testMultipleAccountTypeChangesCreateSeparateAuditTrailEntries(): void
    {
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

        $this->actingAs($editor);

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

        $this->assertCount(3, $auditLogs); // 3 changes from initial state

        // Verify each change is properly logged
        for ($i = 0; $i < 3; $i++) {
            $log = $auditLogs[$i];
            $properties = $log->properties;

            $this->assertEquals($typeChanges[$i]->value, $properties['old']['account_type']);
            $this->assertEquals($typeChanges[$i + 1]->value, $properties['attributes']['account_type']);
            $this->assertEquals($editor->getKey(), $log->causer_id);
        }
    }

    public function testAccountTypeChangesAttributedToCorrectUsers(): void
    {
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
        $this->actingAs($editor1);
        $company->account_type = AccountType::CUSTOMER;
        $company->save();

        sleep(1);

        // Second user makes a change
        $this->actingAs($editor2);
        $company->account_type = AccountType::PARTNER;
        $company->save();

        // Verify both changes are attributed to correct users
        $auditLogs = Activity::where('subject_type', 'company')
            ->where('subject_id', $company->id)
            ->where('event', 'updated')
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $auditLogs);

        // First change by editor1
        $firstLog = $auditLogs[0];
        $this->assertEquals($editor1->getKey(), $firstLog->causer_id);
        $this->assertEquals(AccountType::PROSPECT->value, $firstLog->properties['old']['account_type']);
        $this->assertEquals(AccountType::CUSTOMER->value, $firstLog->properties['attributes']['account_type']);

        // Second change by editor2
        $secondLog = $auditLogs[1];
        $this->assertEquals($editor2->getKey(), $secondLog->causer_id);
        $this->assertEquals(AccountType::CUSTOMER->value, $secondLog->properties['old']['account_type']);
        $this->assertEquals(AccountType::PARTNER->value, $secondLog->properties['attributes']['account_type']);
    }

    public function testAccountTypeAuditTrailIncludesAccurateTimestamps(): void
    {
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
        
        $this->actingAs($editor);
        $company->account_type = AccountType::CUSTOMER;
        $company->save();
        
        // Record time after change
        $afterChange = now();

        // Verify audit log timestamp is within expected range
        $auditLog = Activity::where('subject_type', 'company')
            ->where('subject_id', $company->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $auditLog->created_at);
        $this->assertTrue($auditLog->created_at->between($beforeChange, $afterChange));

        // Verify updated_at is also set
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $auditLog->updated_at);
        $this->assertTrue($auditLog->updated_at->between($beforeChange, $afterChange));
    }

    public function testAccountTypeAuditTrailHandlesNullTransitions(): void
    {
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

        $this->actingAs($editor);

        // Change from null to a value
        $company->account_type = AccountType::PROSPECT;
        $company->save();

        // Verify null to value transition is logged
        $nullToValueLog = Activity::where('subject_type', 'company')
            ->where('subject_id', $company->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($nullToValueLog);
        $this->assertNull($nullToValueLog->properties['old']['account_type']);
        $this->assertEquals(AccountType::PROSPECT->value, $nullToValueLog->properties['attributes']['account_type']);

        // Change from value back to null
        $company->account_type = null;
        $company->save();

        // Verify value to null transition is logged
        $valueToNullLog = Activity::where('subject_type', 'company')
            ->where('subject_id', $company->id)
            ->where('event', 'updated')
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($valueToNullLog);
        $this->assertEquals(AccountType::PROSPECT->value, $valueToNullLog->properties['old']['account_type']);
        $this->assertNull($valueToNullLog->properties['attributes']['account_type']);
    }
}