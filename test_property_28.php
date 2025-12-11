<?php

declare(strict_types=1);

/**
 * Property 28 Test: Account Type Change Audit Trail
 *
 * This standalone test validates that account type changes are properly logged
 * in the activity history system. It tests the core requirement that when an
 * account's type is changed, the system preserves both the old and new values
 * in the activity log for audit trail purposes.
 *
 * @see .kiro/specs/accounts-module/design.md Property 28
 * @see tests/Unit/Models/CompanyTest.php for the formal test suite version
 *
 * Requirements Validated:
 * - 11.4: Account type changes must be preserved in activity history
 *
 * Test Flow:
 * 1. Create a company with initial account type (CUSTOMER)
 * 2. Clear any creation activities to isolate the update test
 * 3. Change account type to a different value (PROSPECT)
 * 4. Verify the change is logged in activities with old and new values
 *
 * Expected Behavior:
 * - Activity log should contain an 'updated' event
 * - Changes should include both 'attributes' (new values) and 'old' (previous values)
 * - Account type change should be specifically tracked
 *
 * @author Kiro Documentation System
 *
 * @version 1.0.0
 *
 * @since 2025-12-11
 */

require_once 'vendor/autoload.php';

use App\Enums\AccountType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;

// Simple test to check if Property 28 works
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create test data
$team = \App\Models\Team::factory()->create();
$user = \App\Models\User::factory()->create(['current_team_id' => $team->getKey()]);

// Set the authenticated user
auth()->login($user);

// Create a company with an initial account type
$initialType = AccountType::CUSTOMER;
$company = \App\Models\Company::factory()->create([
    'team_id' => $team->getKey(),
    'account_type' => $initialType,
]);

echo 'Company created with account type: ' . $company->account_type->value . "\n";

// Clear any creation activities
$company->activities()->delete();

// Change the account type
$newType = AccountType::PROSPECT;
$company->account_type = $newType;
$company->save();

echo 'Company updated to account type: ' . $company->account_type->value . "\n";

// Check activities
$activities = $company->activities()->where('event', 'updated')->get();
echo 'Found ' . $activities->count() . " update activities\n";

if ($activities->isNotEmpty()) {
    $activity = $activities->first();
    echo 'Activity changes raw: ' . var_export($activity->changes, true) . "\n";
    echo 'Activity changes type: ' . gettype($activity->changes) . "\n";

    // Handle both JSON string and array formats
    $changes = $activity->changes;

    // If it's a Collection, get the first item
    if ($changes instanceof Collection) {
        $changes = $changes->first();
    }

    // If it's an array with a JSON string as first element
    if (is_array($changes) && count($changes) === 1 && is_string($changes[0])) {
        $changes = json_decode($changes[0], true);
    } elseif (is_string($changes)) {
        $changes = json_decode($changes, true);
    } else {
        $changes = (array) $changes;
    }

    echo 'Parsed changes: ' . var_export($changes, true) . "\n";

    if (isset($changes['attributes']['account_type'])) {
        echo "SUCCESS: Account type change was logged!\n";
        echo 'Old value: ' . $changes['old']['account_type'] . "\n";
        echo 'New value: ' . $changes['attributes']['account_type'] . "\n";
    } else {
        echo "FAILURE: Account type change was not logged in activity\n";
    }
} else {
    echo "FAILURE: No update activities found\n";
}
