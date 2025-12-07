<?php

declare(strict_types=1);

use App\Models\AccountTeamMember;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->team->users()->attach($this->user);
    actingAs($this->user);
    $this->user->switchTeam($this->team);
});

describe('ViewCompany Performance', function (): void {
    test('loads page with minimal queries', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);
        
        // Add some related data
        $teamMember = User::factory()->create();
        $this->team->users()->attach($teamMember);
        AccountTeamMember::create([
            'account_id' => $company->id,
            'user_id' => $teamMember->id,
            'role' => \App\Enums\AccountTeamRole::ACCOUNT_MANAGER,
            'access_level' => \App\Enums\AccountTeamAccessLevel::EDIT,
        ]);
        
        // Add attachments
        $company->addMedia(storage_path('app/test-file.txt'))
            ->withCustomProperties(['uploaded_by' => $this->user->id])
            ->toMediaCollection('attachments');
        
        DB::enableQueryLog();
        
        Livewire::test(\App\Filament\Resources\CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful();
        
        $queries = DB::getQueryLog();
        
        // Should be under 15 queries with proper eager loading
        expect(count($queries))->toBeLessThan(15);
    });
    
    test('does not have N+1 queries with multiple attachments', function (): void {
        $company = Company::factory()->create(['team_id' => $this->team->id]);
        
        // Add 10 attachments with different uploaders
        $uploaders = User::factory()->count(5)->create();
        foreach ($uploaders as $uploader) {
            $this->team->users()->attach($uploader);
            for ($i = 0; $i < 2; $i++) {
                $company->addMedia(storage_path('app/test-file.txt'))
                    ->withCustomProperties(['uploaded_by' => $uploader->id])
                    ->toMediaCollection('attachments');
            }
        }
        
        DB::enableQueryLog();
        
        Livewire::test(\App\Filament\Resources\CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful();
        
        $queries = DB::getQueryLog();
        
        // Count User::find() queries - should be 0 or 1 (batch query)
        $userFindQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'select * from "users" where "users"."id" = ?');
        })->count();
        
        expect($userFindQueries)->toBe(0, 'Should not have individual User::find() queries');
    });
    
    test('eager loads required relationships', function (): void {
        $parent = Company::factory()->create(['team_id' => $this->team->id]);
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'parent_company_id' => $parent->id,
        ]);
        
        DB::enableQueryLog();
        
        Livewire::test(\App\Filament\Resources\CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
            ->assertSuccessful()
            ->assertSee($parent->name);
        
        $queries = DB::getQueryLog();
        
        // Should not have separate queries for creator, accountOwner, parentCompany
        $relationQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'select * from "users" where "users"."id" in')
                || str_contains($query['query'], 'select * from "companies" where "companies"."id" in');
        })->count();
        
        // Should have at most 2 queries (one for users, one for companies)
        expect($relationQueries)->toBeLessThanOrEqual(2);
    });
});
