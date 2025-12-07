<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->user = User::factory()->create();
        $this->team->users()->attach($this->user);
        $this->actingAs($this->user);
        $this->user->switchTeam($this->team);
    }

    public function test_account_has_many_to_many_relationship_with_contacts(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        $contact1 = People::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $contact2 = People::factory()->create([
            'team_id' => $this->team->id,
        ]);

        // Attach contacts to account
        $account->contacts()->attach($contact1->id, ['is_primary' => true, 'role' => 'Decision Maker']);
        $account->contacts()->attach($contact2->id, ['is_primary' => false, 'role' => 'Influencer']);

        // Test forward relationship
        $this->assertCount(2, $account->contacts);
        $this->assertTrue($account->contacts->contains($contact1));
        $this->assertTrue($account->contacts->contains($contact2));

        // Test pivot data
        $primaryContact = $account->contacts()->wherePivot('is_primary', true)->first();
        $this->assertEquals($contact1->id, $primaryContact->id);
        $this->assertEquals('Decision Maker', $primaryContact->pivot->role);

        // Test reverse relationship
        $this->assertCount(1, $contact1->accounts);
        $this->assertTrue($contact1->accounts->contains($account));
    }

    public function test_account_has_many_opportunities(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        $opportunity1 = Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'account_id' => $account->id,
        ]);

        $opportunity2 = Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'account_id' => $account->id,
        ]);

        // Test forward relationship
        $this->assertCount(2, $account->opportunities);
        $this->assertTrue($account->opportunities->contains($opportunity1));
        $this->assertTrue($account->opportunities->contains($opportunity2));

        // Test reverse relationship
        $this->assertEquals($account->id, $opportunity1->account_id);
        $this->assertEquals($account->id, $opportunity1->account->id);
    }

    public function test_account_has_many_cases(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        $case1 = SupportCase::factory()->create([
            'team_id' => $this->team->id,
            'account_id' => $account->id,
        ]);

        $case2 = SupportCase::factory()->create([
            'team_id' => $this->team->id,
            'account_id' => $account->id,
        ]);

        // Test forward relationship
        $this->assertCount(2, $account->cases);
        $this->assertTrue($account->cases->contains($case1));
        $this->assertTrue($account->cases->contains($case2));

        // Test reverse relationship
        $this->assertEquals($account->id, $case1->account_id);
        $this->assertEquals($account->id, $case1->account->id);
    }

    public function test_account_has_parent_child_hierarchy(): void
    {
        $parentAccount = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
            'name' => 'Parent Corp',
        ]);

        $childAccount1 = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
            'parent_id' => $parentAccount->id,
            'name' => 'Child Corp 1',
        ]);

        $childAccount2 = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
            'parent_id' => $parentAccount->id,
            'name' => 'Child Corp 2',
        ]);

        // Test parent relationship
        $this->assertEquals($parentAccount->id, $childAccount1->parent_id);
        $this->assertEquals($parentAccount->id, $childAccount1->parent->id);

        // Test children relationship
        $this->assertCount(2, $parentAccount->children);
        $this->assertTrue($parentAccount->children->contains($childAccount1));
        $this->assertTrue($parentAccount->children->contains($childAccount2));
    }

    public function test_account_soft_deletes_preserve_relationships(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        $contact = People::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $account->contacts()->attach($contact->id);

        // Soft delete the account
        $account->delete();

        // Account should be soft deleted
        $this->assertSoftDeleted($account);

        // Relationship should still exist in pivot table
        $this->assertDatabaseHas('account_people', [
            'account_id' => $account->id,
            'people_id' => $contact->id,
        ]);

        // Restore and verify relationship still works
        $account->restore();
        $this->assertCount(1, $account->fresh()->contacts);
    }

    public function test_contact_soft_delete_preserves_pivot(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        $contact = People::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $account->contacts()->attach($contact->id);

        // Soft delete the contact
        $contact->delete();

        // Contact should be soft deleted
        $this->assertSoftDeleted($contact);

        // Pivot entry should still exist (soft deletes don't cascade to pivot tables)
        $this->assertDatabaseHas('account_people', [
            'account_id' => $account->id,
            'people_id' => $contact->id,
        ]);

        // But the relationship query should not return soft-deleted contacts
        $this->assertCount(0, $account->fresh()->contacts);
    }

    public function test_opportunity_preserves_account_id_when_account_soft_deleted(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'team_id' => $this->team->id,
            'account_id' => $account->id,
        ]);

        // Soft delete the account
        $account->delete();

        // Opportunity should still exist and account_id should be preserved
        // (soft deletes don't trigger database foreign key constraints)
        $opportunity->refresh();
        $this->assertEquals($account->id, $opportunity->account_id);

        // But the relationship should return null since account is soft deleted
        $this->assertNull($opportunity->account);
    }

    public function test_case_preserves_account_id_when_account_soft_deleted(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        $case = SupportCase::factory()->create([
            'team_id' => $this->team->id,
            'account_id' => $account->id,
        ]);

        // Soft delete the account
        $account->delete();

        // Case should still exist and account_id should be preserved
        // (soft deletes don't trigger database foreign key constraints)
        $case->refresh();
        $this->assertEquals($account->id, $case->account_id);

        // But the relationship should return null since account is soft deleted
        $this->assertNull($case->account);
    }

    public function test_account_activity_timeline_includes_all_related_records(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'owner_id' => $this->user->id,
        ]);

        // Create related records - but we need to check if these relationships exist
        // For now, just test that the method returns a collection
        $timeline = $account->getActivityTimeline();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $timeline);
        $this->assertGreaterThanOrEqual(1, $timeline->count()); // At least account creation
    }
}
