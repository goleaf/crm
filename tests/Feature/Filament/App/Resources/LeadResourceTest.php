<?php

declare(strict_types=1);

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Filament\Resources\LeadResource\Pages\CreateLead;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->personalTeam());
});

describe('List Page', function (): void {
    it('can render the index page', function (): void {
        livewire(ListLeads::class)
            ->assertOk();
    });

    it('can render `:dataset` column', function (string $column): void {
        livewire(ListLeads::class)
            ->assertCanRenderTableColumn($column);
    })->with(['name', 'company_name', 'status', 'source', 'grade', 'score', 'assignedTo.name']);

    it('has `:dataset` column', function (string $column): void {
        livewire(ListLeads::class)
            ->assertTableColumnExists($column);
    })->with(['name', 'company_name', 'status', 'source', 'grade', 'score', 'assignedTo.name', 'territory', 'tags', 'created_at', 'updated_at']);

    it('shows `:dataset` column', function (string $column): void {
        livewire(ListLeads::class)
            ->assertTableColumnVisible($column);
    })->with(['name', 'company_name', 'status', 'source', 'grade', 'score', 'assignedTo.name']);

    it('can sort `:dataset` column', function (string $column): void {
        $records = Lead::factory(3)->for($this->user->personalTeam(), 'team')->create();

        $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
            ? fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column)?->value
            : $column;

        livewire(ListLeads::class)
            ->sortTable($column)
            ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
            ->sortTable($column, 'desc')
            ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
    })->with(['name', 'company_name', 'score', 'created_at', 'updated_at']);

    it('can search `:dataset` column', function (string $column): void {
        $records = Lead::factory(3)->for($this->user->personalTeam(), 'team')->create();
        $search = data_get($records->first(), $column);

        livewire(ListLeads::class)
            ->searchTable($search instanceof BackedEnum ? $search->value : $search)
            ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) === $search))
            ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) !== $search));
    })->with(['name', 'company_name']);

    it('cannot display trashed records by default', function (): void {
        $records = Lead::factory()->count(4)->for($this->user->personalTeam(), 'team')->create();
        $trashedRecords = Lead::factory()->trashed()->count(6)->for($this->user->personalTeam(), 'team')->create();

        livewire(ListLeads::class)
            ->assertCanSeeTableRecords($records)
            ->assertCanNotSeeTableRecords($trashedRecords)
            ->assertCountTableRecords(4);
    });

    it('can paginate records', function (): void {
        $records = Lead::factory(20)->for($this->user->personalTeam(), 'team')->create();

        livewire(ListLeads::class)
            ->assertCanSeeTableRecords($records->take(10), inOrder: true)
            ->call('gotoPage', 2)
            ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
    });

    it('has `:dataset` filter', function (string $filter): void {
        livewire(ListLeads::class)
            ->assertTableFilterExists($filter);
    })->with(['status', 'source', 'grade', 'assignment_strategy', 'nurture_status', 'creation_source', 'tags', 'trashed']);

    it('can filter by tags', function (): void {
        $vipTag = App\Models\Tag::factory()->for($this->user->personalTeam(), 'team')->create(['name' => 'VIP']);
        $partnerTag = App\Models\Tag::factory()->for($this->user->personalTeam(), 'team')->create(['name' => 'Partner']);

        $matching = Lead::factory()->for($this->user->personalTeam(), 'team')->create();
        $matching->tags()->attach([$vipTag->getKey(), $partnerTag->getKey()]);

        $nonMatching = Lead::factory()->for($this->user->personalTeam(), 'team')->create();
        $nonMatching->tags()->attach([$partnerTag->getKey()]);

        livewire(ListLeads::class)
            ->filterTable('tags', [$vipTag])
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$nonMatching]);
    });
});

describe('Create Page', function (): void {
    it('can render the create page', function (): void {
        livewire(CreateLead::class)
            ->assertOk();
    });

    it('can create a lead with required fields', function (): void {
        $data = [
            'name' => 'John Doe',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'team_id' => $this->user->personalTeam()->id,
        ]);
    });

    it('can create a lead with all profile fields', function (): void {
        $company = Company::factory()->for($this->user->personalTeam(), 'team')->create();

        $data = [
            'name' => 'Jane Smith',
            'job_title' => 'Marketing Director',
            'company_name' => 'Acme Corp',
            'company_id' => $company->id,
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'mobile' => '+0987654321',
            'website' => 'https://example.com',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::REFERRAL->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'name' => 'Jane Smith',
            'job_title' => 'Marketing Director',
            'company_name' => 'Acme Corp',
            'company_id' => $company->id,
            'email' => 'jane@example.com',
        ]);
    });

    it('can create a lead with status and routing fields', function (): void {
        $assignee = User::factory()->create();

        $data = [
            'name' => 'Bob Johnson',
            'status' => LeadStatus::QUALIFIED->value,
            'source' => LeadSource::TRADE_SHOW->value,
            'score' => 85,
            'grade' => LeadGrade::A->value,
            'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN->value,
            'assigned_to_id' => $assignee->id,
            'territory' => 'West Coast',
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'name' => 'Bob Johnson',
            'status' => LeadStatus::QUALIFIED->value,
            'score' => 85,
            'grade' => LeadGrade::A->value,
            'assigned_to_id' => $assignee->id,
            'territory' => 'West Coast',
        ]);
    });

    it('can create a lead with nurturing fields', function (): void {
        $data = [
            'name' => 'Alice Brown',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
            'nurture_status' => LeadNurtureStatus::IN_PROGRESS->value,
            'nurture_program' => 'Q4 Campaign',
            'next_nurture_touch_at' => now()->addDays(7)->toDateTimeString(),
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'name' => 'Alice Brown',
            'nurture_status' => LeadNurtureStatus::IN_PROGRESS->value,
            'nurture_program' => 'Q4 Campaign',
        ]);
    });

    it('can create a lead with tags', function (): void {
        $tags = Tag::factory()->count(3)->for($this->user->personalTeam(), 'team')->create();

        $data = [
            'name' => 'Charlie Wilson',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
            'tags' => $tags->pluck('id')->toArray(),
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $lead = Lead::where('name', 'Charlie Wilson')->first();
        expect($lead->tags)->toHaveCount(3);
    });

    it('validates required name field', function (): void {
        $data = [
            'name' => '',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    });

    it('validates required status field', function (): void {
        $data = [
            'name' => 'Test Lead',
            'status' => null,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['status' => 'required']);
    });

    it('validates required source field', function (): void {
        $data = [
            'name' => 'Test Lead',
            'status' => LeadStatus::NEW->value,
            'source' => null,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['source' => 'required']);
    });

    it('validates email format', function (): void {
        $data = [
            'name' => 'Test Lead',
            'email' => 'invalid-email',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['email']);
    });

    it('validates website URL format', function (): void {
        $data = [
            'name' => 'Test Lead',
            'website' => 'not-a-url',
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['website']);
    });

    it('validates score range', function (): void {
        $data = [
            'name' => 'Test Lead',
            'score' => 1500,
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['score']);
    });

    it('validates duplicate score range', function (): void {
        $data = [
            'name' => 'Test Lead',
            'duplicate_score' => 150,
            'status' => LeadStatus::NEW->value,
            'source' => LeadSource::WEBSITE->value,
            'assignment_strategy' => LeadAssignmentStrategy::MANUAL->value,
        ];

        livewire(CreateLead::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['duplicate_score']);
    });
});
