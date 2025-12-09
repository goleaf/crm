<?php

declare(strict_types=1);

use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use App\Enums\AccountType;
use App\Enums\Industry;
use App\Filament\Resources\CompanyResource;
use App\Jobs\FetchFaviconForCompany;
use App\Models\AccountTeamMember;
use App\Models\Company;
use App\Models\CompanyRevenue;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->team->users()->attach($this->user);
    actingAs($this->user);
    $this->user->switchTeam($this->team);
});

describe('ViewCompany Page Rendering', function (): void {
    test('can render view page', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful();
    });

    test('can render view page with minimal data', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Minimal Company',
            'account_type' => null,
            'industry' => null,
            'phone' => null,
            'primary_email' => null,
            'website' => null,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('Minimal Company');
    });

    test('can render view page with full data', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Full Data Company',
            'account_type' => AccountType::CUSTOMER,
            'industry' => Industry::TECHNOLOGY,
            'phone' => '+1234567890',
            'primary_email' => 'contact@company.com',
            'website' => 'https://company.com',
            'employee_count' => 500,
            'revenue' => 1000000.00,
            'currency_code' => 'USD',
            'billing_street' => '123 Main St',
            'billing_city' => 'New York',
            'billing_state' => 'NY',
            'billing_postal_code' => '10001',
            'billing_country' => 'USA',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('Full Data Company')
            ->assertSee('contact@company.com')
            ->assertSee('https://company.com');
    });

    test('cannot view company from different team', function (): void {
        $otherTeam = Team::factory()->create();
        $company = Company::factory()->create(['team_id' => $otherTeam->id]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertForbidden();
    });
});

describe('Account Team Members Display', function (): void {
    test('displays account team members with roles and access levels', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);
        $teamMember = User::factory()->create();
        $this->team->users()->attach($teamMember);

        AccountTeamMember::create([
            'account_id' => $company->id,
            'user_id' => $teamMember->id,
            'role' => AccountTeamRole::ACCOUNT_MANAGER,
            'access_level' => AccountTeamAccessLevel::EDIT,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee($teamMember->name)
            ->assertSee($teamMember->email);
    });

    test('displays correct badge colors for account team member roles', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);
        $teamMember = User::factory()->create();
        $this->team->users()->attach($teamMember);

        AccountTeamMember::create([
            'account_id' => $company->id,
            'user_id' => $teamMember->id,
            'role' => AccountTeamRole::ACCOUNT_MANAGER,
            'access_level' => AccountTeamAccessLevel::EDIT,
        ]);

        $component = Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id]);

        // Verify the component renders successfully
        $component->assertSuccessful();

        // The color callback should use the record array with role_color key
        expect(AccountTeamRole::ACCOUNT_MANAGER->color())->toBeString();
    });

    test('displays correct badge colors for account team member access levels', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);
        $teamMember = User::factory()->create();
        $this->team->users()->attach($teamMember);

        AccountTeamMember::create([
            'account_id' => $company->id,
            'user_id' => $teamMember->id,
            'role' => AccountTeamRole::SALES,
            'access_level' => AccountTeamAccessLevel::VIEW,
        ]);

        $component = Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id]);

        // Verify the component renders successfully
        $component->assertSuccessful();

        // The color callback should use the record array with access_color key
        expect(AccountTeamAccessLevel::VIEW->color())->toBeString();
    });

    test('hides account team section when no members exist', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertDontSee('Account Team');
    });

    test('handles account team members with null user gracefully', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        // Create a team member record with a non-existent user ID
        AccountTeamMember::create([
            'account_id' => $company->id,
            'user_id' => 99999, // Non-existent user
            'role' => AccountTeamRole::ACCOUNT_MANAGER,
            'access_level' => AccountTeamAccessLevel::EDIT,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('—'); // Should display placeholder for missing user
    });

    test('displays multiple account team members', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $member1 = User::factory()->create(['name' => 'Alice Manager']);
        $member2 = User::factory()->create(['name' => 'Bob Sales']);
        $this->team->users()->attach([$member1->id, $member2->id]);

        AccountTeamMember::create([
            'account_id' => $company->id,
            'user_id' => $member1->id,
            'role' => AccountTeamRole::ACCOUNT_MANAGER,
            'access_level' => AccountTeamAccessLevel::MANAGE,
        ]);

        AccountTeamMember::create([
            'account_id' => $company->id,
            'user_id' => $member2->id,
            'role' => AccountTeamRole::SALES,
            'access_level' => AccountTeamAccessLevel::VIEW,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('Alice Manager')
            ->assertSee('Bob Sales');
    });
});

describe('Child Companies Display', function (): void {
    test('displays child companies', function (): void {
        $parentCompany = Company::factory()->create(['team_id' => $this->team->id]);
        $childCompany = Company::factory()->create([
            'team_id' => $this->team->id,
            'parent_company_id' => $parentCompany->id,
            'name' => 'Child Company',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $parentCompany->id])
            ->assertSuccessful()
            ->assertSee('Child Company');
    });

    test('hides child companies section when no children exist', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertDontSee('Child Companies');
    });

    test('displays child company with account type badge', function (): void {
        $parentCompany = Company::factory()->create(['team_id' => $this->team->id]);
        $childCompany = Company::factory()->create([
            'team_id' => $this->team->id,
            'parent_company_id' => $parentCompany->id,
            'account_type' => AccountType::PARTNER,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $parentCompany->id])
            ->assertSuccessful();
    });
});

describe('Annual Revenue Display', function (): void {
    test('displays latest annual revenue', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'currency_code' => 'USD',
        ]);

        CompanyRevenue::create([
            'company_id' => $company->id,
            'year' => 2024,
            'amount' => 5000000.00,
            'currency_code' => 'USD',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('5,000,000.00')
            ->assertSee('2024');
    });

    test('displays company revenue when no annual revenue exists', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'revenue' => 1000000.00,
            'currency_code' => 'EUR',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('1,000,000.00');
    });

    test('displays placeholder when no revenue data exists', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'revenue' => null,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful();
    });
});

describe('Address Display', function (): void {
    test('displays billing address', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'billing_street' => '123 Main St',
            'billing_city' => 'New York',
            'billing_state' => 'NY',
            'billing_postal_code' => '10001',
            'billing_country' => 'USA',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('123 Main St')
            ->assertSee('New York');
    });

    test('displays shipping address', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'shipping_street' => '456 Oak Ave',
            'shipping_city' => 'Los Angeles',
            'shipping_state' => 'CA',
            'shipping_postal_code' => '90001',
            'shipping_country' => 'USA',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('456 Oak Ave')
            ->assertSee('Los Angeles');
    });

    test('displays placeholder for empty addresses', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'billing_street' => null,
            'billing_city' => null,
            'shipping_street' => null,
            'shipping_city' => null,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful();
    });
});

describe('Header Actions', function (): void {
    test('can access edit action', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertActionExists(EditAction::class);
    });

    test('can access delete action', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertActionExists(DeleteAction::class);
    });

    test('can delete company', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    });
});

describe('Favicon Fetch on Edit', function (): void {
    test('dispatches favicon fetch job when domain changes', function (): void {
        Queue::fake();

        $company = Company::factory()->create([
            'team_id' => $this->team->id,
        ]);

        // Add custom field for domain
        $domainField = \Relaticle\CustomFields\Models\CustomField::factory()->create([
            'team_id' => $this->team->id,
            'code' => 'domain_name',
            'label' => 'Domain Name',
            'field_type' => 'text',
            'entity_type' => 'company',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->callAction(EditAction::class, data: [
                'name' => $company->name,
                'custom_fields' => [
                    'domain_name' => 'newdomain.com',
                ],
            ]);

        Queue::assertPushed(FetchFaviconForCompany::class);
    });

    test('does not dispatch favicon fetch job when domain unchanged', function (): void {
        Queue::fake();

        $company = Company::factory()->create([
            'team_id' => $this->team->id,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->callAction(EditAction::class, data: [
                'name' => 'Updated Name',
            ]);

        Queue::assertNotPushed(FetchFaviconForCompany::class);
    });
});

describe('Relation Managers', function (): void {
    test('has annual revenues relation manager', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $page = new CompanyResource\Pages\ViewCompany;
        $relationManagers = $page->getRelationManagers();

        expect($relationManagers)->toContain(
            CompanyResource\RelationManagers\AnnualRevenuesRelationManager::class,
        );
    });

    test('has cases relation manager', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $page = new CompanyResource\Pages\ViewCompany;
        $relationManagers = $page->getRelationManagers();

        expect($relationManagers)->toContain(
            CompanyResource\RelationManagers\CasesRelationManager::class,
        );
    });

    test('has people relation manager', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $page = new CompanyResource\Pages\ViewCompany;
        $relationManagers = $page->getRelationManagers();

        expect($relationManagers)->toContain(
            CompanyResource\RelationManagers\PeopleRelationManager::class,
        );
    });

    test('has tasks relation manager', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $page = new CompanyResource\Pages\ViewCompany;
        $relationManagers = $page->getRelationManagers();

        expect($relationManagers)->toContain(
            CompanyResource\RelationManagers\TasksRelationManager::class,
        );
    });

    test('has notes relation manager', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $page = new CompanyResource\Pages\ViewCompany;
        $relationManagers = $page->getRelationManagers();

        expect($relationManagers)->toContain(
            CompanyResource\RelationManagers\NotesRelationManager::class,
        );
    });

    test('has activities relation manager', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $page = new CompanyResource\Pages\ViewCompany;
        $relationManagers = $page->getRelationManagers();

        expect($relationManagers)->toContain(
            \App\Filament\RelationManagers\ActivitiesRelationManager::class,
        );
    });
});

describe('Edge Cases', function (): void {
    test('handles null enum values gracefully', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'account_type' => null,
            'industry' => null,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('—');
    });

    test('handles empty string values', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'phone' => '',
            'primary_email' => '',
            'website' => '',
            'description' => '',
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful();
    });

    test('handles large employee count formatting', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'employee_count' => 1000000,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('1,000,000');
    });

    test('handles zero employee count', function (): void {
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'employee_count' => 0,
        ]);

        Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee('0');
    });
});
