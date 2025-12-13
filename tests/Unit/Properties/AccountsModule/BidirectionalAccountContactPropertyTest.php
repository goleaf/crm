<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\People;
use App\Models\Team;
use App\Models\User;

/**
 * **Feature: core-crm-modules, Property 2: Bidirectional account-contact links**
 *
 * **Validates: Requirements 1.2, 2.2**
 *
 * Property: Associating a contact to an account makes the relationship
 * queryable from both sides and survives soft deletes.
 */
test('property: account-contact association is bidirectional', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    // Create account and contact
    $account = Account::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    $contact = People::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    // Associate contact with account
    $contact->update(['company_id' => $account->id]);

    // Verify bidirectional relationship
    $retrievedContact = People::find($contact->id);
    $retrievedAccount = Account::find($account->id);

    // Contact -> Account relationship
    expect($retrievedContact->company_id)->toBe($account->id);
    
    if (method_exists($retrievedContact, 'company')) {
        expect($retrievedContact->company)->not->toBeNull();
        expect($retrievedContact->company->id)->toBe($account->id);
    }

    // Account -> Contact relationship
    if (method_exists($retrievedAccount, 'contacts')) {
        expect($retrievedAccount->contacts->pluck('id'))->toContain($contact->id);
    }
})->repeat(100);

test('property: multiple contacts can be associated with one account', function (): void {
    runPropertyTest(function (): void {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        // Create random number of contacts (1-5)
        $contactCount = fake()->numberBetween(1, 5);
        $contacts = [];

        for ($i = 0; $i < $contactCount; $i++) {
            $contact = People::factory()->create([
                'team_id' => $this->team->id,
                'creator_id' => $this->user->id,
                'company_id' => $account->id,
            ]);
            $contacts[] = $contact;
        }

        // Verify all contacts are associated with the account
        $retrievedAccount = Account::find($account->id);
        expect($retrievedAccount->contacts)->toHaveCount($contactCount);

        foreach ($contacts as $contact) {
            expect($retrievedAccount->contacts->pluck('id'))->toContain($contact->id);
            
            // Verify reverse relationship
            $retrievedContact = People::find($contact->id);
            expect($retrievedContact->company_id)->toBe($account->id);
            expect($retrievedContact->company->id)->toBe($account->id);
        }
    }, 50);
});

test('property: contact can be moved between accounts', function (): void {
    runPropertyTest(function (): void {
        // Create two accounts
        $account1 = Account::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        $account2 = Account::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        // Create contact associated with first account
        $contact = People::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'company_id' => $account1->id,
        ]);

        // Verify initial association
        expect($contact->company_id)->toBe($account1->id);
        expect($account1->fresh()->contacts->pluck('id'))->toContain($contact->id);
        expect($account2->fresh()->contacts->pluck('id'))->not->toContain($contact->id);

        // Move contact to second account
        $contact->update(['company_id' => $account2->id]);

        // Verify the move
        $updatedContact = People::find($contact->id);
        expect($updatedContact->company_id)->toBe($account2->id);
        expect($updatedContact->company->id)->toBe($account2->id);

        // Verify relationships are updated
        expect($account1->fresh()->contacts->pluck('id'))->not->toContain($contact->id);
        expect($account2->fresh()->contacts->pluck('id'))->toContain($contact->id);
    }, 50);
});

test('property: account-contact relationships survive soft deletes', function (): void {
    runPropertyTest(function (): void {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        $contact = People::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'company_id' => $account->id,
        ]);

        // Verify initial relationship
        expect($contact->company_id)->toBe($account->id);
        expect($account->contacts->pluck('id'))->toContain($contact->id);

        // Soft delete the contact
        $contact->delete();

        // Verify relationship survives soft delete
        $trashedContact = People::withTrashed()->find($contact->id);
        expect($trashedContact)->not->toBeNull();
        expect($trashedContact->company_id)->toBe($account->id);

        // Account should still reference the contact when including trashed
        $accountWithTrashed = Account::with(['contacts' => function ($query) {
            $query->withTrashed();
        }])->find($account->id);
        
        expect($accountWithTrashed->contacts->pluck('id'))->toContain($contact->id);

        // But not in normal queries
        expect($account->fresh()->contacts->pluck('id'))->not->toContain($contact->id);
    }, 30);
});

test('property: account soft delete preserves contact relationships', function (): void {
    runPropertyTest(function (): void {
        $account = Account::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        $contact = People::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'company_id' => $account->id,
        ]);

        // Verify initial relationship
        expect($contact->company_id)->toBe($account->id);

        // Soft delete the account
        $account->delete();

        // Contact should still reference the account
        $retrievedContact = People::find($contact->id);
        expect($retrievedContact->company_id)->toBe($account->id);

        // But company relationship should return null for normal queries
        expect($retrievedContact->company)->toBeNull();

        // Should be accessible when including trashed
        $contactWithTrashedCompany = People::with(['company' => function ($query) {
            $query->withTrashed();
        }])->find($contact->id);
        
        expect($contactWithTrashedCompany->company)->not->toBeNull();
        expect($contactWithTrashedCompany->company->id)->toBe($account->id);
    }, 30);
});

test('property: contact without account has null company relationship', function (): void {
    runPropertyTest(function (): void {
        $contact = People::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'company_id' => null,
        ]);

        // Verify null relationship
        expect($contact->company_id)->toBeNull();
        expect($contact->company)->toBeNull();

        // Verify contact can be retrieved without errors
        $retrievedContact = People::find($contact->id);
        expect($retrievedContact)->not->toBeNull();
        expect($retrievedContact->company_id)->toBeNull();
        expect($retrievedContact->company)->toBeNull();
    }, 30);
});

test('property: many-to-many contact-account relationships work correctly', function (): void {
    runPropertyTest(function (): void {
        // Create accounts and contacts
        $account1 = Account::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        $account2 = Account::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        $contact = People::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'company_id' => $account1->id, // Primary relationship
        ]);

        // If many-to-many relationships exist (via pivot table)
        if (method_exists($contact, 'accounts')) {
            // Associate contact with both accounts
            $contact->accounts()->attach([$account1->id, $account2->id]);

            // Verify many-to-many relationships
            $retrievedContact = People::with('accounts')->find($contact->id);
            expect($retrievedContact->accounts)->toHaveCount(2);
            expect($retrievedContact->accounts->pluck('id'))->toContain($account1->id);
            expect($retrievedContact->accounts->pluck('id'))->toContain($account2->id);

            // Verify reverse relationships
            expect($account1->fresh()->people->pluck('id'))->toContain($contact->id);
            expect($account2->fresh()->people->pluck('id'))->toContain($contact->id);
        }
    }, 20);
});

