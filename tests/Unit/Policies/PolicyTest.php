<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\People;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContactPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\NotePolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PeoplePolicy;
use App\Policies\ProductPolicy;
use App\Policies\QuotePolicy;
use App\Policies\SupportCasePolicy;
use App\Policies\TaskPolicy;
use Filament\Facades\Filament;

dataset('policies', [
    [LeadPolicy::class, Lead::class],
    [InvoicePolicy::class, Invoice::class],
    [CompanyPolicy::class, Company::class],
    [ContactPolicy::class, Contact::class],
    [AccountPolicy::class, Account::class],
    [CustomerPolicy::class, Customer::class],
    [DeliveryPolicy::class, Delivery::class],
    [NotePolicy::class, Note::class],
    [OpportunityPolicy::class, Opportunity::class],
    [OrderPolicy::class, Order::class],
    [PeoplePolicy::class, People::class],
    [ProductPolicy::class, Product::class],
    [QuotePolicy::class, Quote::class],
    [SupportCasePolicy::class, SupportCase::class],
    [TaskPolicy::class, Task::class],
]);

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->user->email_verified_at = now();
    $this->user->save();

    Filament::setTenant($this->team);
});

test('viewAny allows verified user in tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    expect($policy->viewAny($this->user))->toBeTrue();
})->with('policies');

test('viewAny denies user without verified email', function (string $policyClass, string $modelClass) {
    $this->user->email_verified_at = null;
    $this->user->save();

    $policy = new $policyClass;

    expect($policy->viewAny($this->user))->toBeFalse();
})->with('policies');

test('view allows user to see resource in their tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    $resource = $modelClass::factory()->create(['team_id' => $this->team->id]);

    expect($policy->view($this->user, $resource))->toBeTrue();
})->with('policies');

test('view denies user from seeing resource in different tenant', function (string $policyClass, string $modelClass) {
    $otherTeam = Team::factory()->create();
    $policy = new $policyClass;

    $resource = $modelClass::factory()->create(['team_id' => $otherTeam->id]);

    expect($policy->view($this->user, $resource))->toBeFalse();
})->with('policies');

test('create allows verified user in tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    expect($policy->create($this->user))->toBeTrue();
})->with('policies');

test('update allows user to modify resource in their tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    $resource = $modelClass::factory()->create(['team_id' => $this->team->id]);

    expect($policy->update($this->user, $resource))->toBeTrue();
})->with('policies');

test('update denies user from modifying resource in different tenant', function (string $policyClass, string $modelClass) {
    $otherTeam = Team::factory()->create();
    $policy = new $policyClass;

    $resource = $modelClass::factory()->create(['team_id' => $otherTeam->id]);

    expect($policy->update($this->user, $resource))->toBeFalse();
})->with('policies');

test('delete allows user to remove resource in their tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    $resource = $modelClass::factory()->create(['team_id' => $this->team->id]);

    expect($policy->delete($this->user, $resource))->toBeTrue();
})->with('policies');

test('deleteAny allows verified user in tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    expect($policy->deleteAny($this->user))->toBeTrue();
})->with('policies');

test('restore allows user to restore resource in their tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    $resource = $modelClass::factory()->create(['team_id' => $this->team->id]);

    expect($policy->restore($this->user, $resource))->toBeTrue();
})->with('policies');

test('restoreAny allows verified user in tenant', function (string $policyClass, string $modelClass) {
    $policy = new $policyClass;

    expect($policy->restoreAny($this->user))->toBeTrue();
})->with('policies');

test('forceDelete requires admin role', function (string $policyClass, string $modelClass) {
    $this->user->currentTeam->users()->updateExistingPivot($this->user->id, ['role' => 'admin']);

    $policy = new $policyClass;
    $resource = $modelClass::factory()->create(['team_id' => $this->team->id]);

    expect($policy->forceDelete($this->user, $resource))->toBeTrue();
})->with('policies');

test('forceDelete denies non-admin user', function (string $policyClass, string $modelClass) {
    $this->user->currentTeam->users()->updateExistingPivot($this->user->id, ['role' => 'editor']);

    $policy = new $policyClass;
    $resource = $modelClass::factory()->create(['team_id' => $this->team->id]);

    expect($policy->forceDelete($this->user, $resource))->toBeFalse();
})->with('policies');

test('forceDeleteAny requires admin role', function (string $policyClass, string $modelClass) {
    $this->user->currentTeam->users()->updateExistingPivot($this->user->id, ['role' => 'admin']);

    $policy = new $policyClass;

    expect($policy->forceDeleteAny($this->user))->toBeTrue();
})->with('policies');

test('forceDeleteAny denies non-admin user', function (string $policyClass, string $modelClass) {
    $this->user->currentTeam->users()->updateExistingPivot($this->user->id, ['role' => 'editor']);

    $policy = new $policyClass;

    expect($policy->forceDeleteAny($this->user))->toBeFalse();
})->with('policies');
