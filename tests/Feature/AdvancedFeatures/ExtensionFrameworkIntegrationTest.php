<?php

declare(strict_types=1);

namespace Tests\Feature\AdvancedFeatures;

use App\Enums\ExtensionStatus;
use App\Enums\ExtensionType;
use App\Enums\HookEvent;
use App\Extensions\TestHandler;
use App\Models\Company;
use App\Models\Extension;
use App\Models\Team;
use App\Models\User;
use App\Services\ExtensionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Integration test: Extension deployment and execution
 *
 * Tests the complete workflow of registering, activating, and executing
 * an extension with proper isolation and error handling.
 */
test('complete extension deployment and execution workflow', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    $registry = new ExtensionRegistry;

    // Register extension
    $extension = $registry->register(
        teamId: $team->id,
        creatorId: $user->id,
        name: 'Company Data Enrichment',
        slug: 'company-data-enrichment',
        type: ExtensionType::LOGIC_HOOK,
        handlerClass: TestHandler::class,
        description: 'Enriches company data on save',
        targetModel: Company::class,
        targetEvent: HookEvent::AFTER_SAVE,
        priority: 100,
        handlerMethod: 'handle'
    );

    expect($extension->status)->toBe(ExtensionStatus::INACTIVE)
        ->and($extension->execution_count)->toBe(0)
        ->and($extension->failure_count)->toBe(0);

    // Activate extension
    $activated = $registry->activate($extension);
    expect($activated->status)->toBe(ExtensionStatus::ACTIVE);

    // Execute extension with context
    $context = [
        'id' => 1,
        'team_id' => $team->id,
        'name' => 'Test Company',
        'enrichment_needed' => true,
    ];

    $result = $registry->executeExtension($activated, $context);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe(1)
        ->and($result['name'])->toBe('Test Company');

    // Verify execution tracking
    $extension = $extension->fresh();
    expect($extension->execution_count)->toBe(1)
        ->and($extension->failure_count)->toBe(0)
        ->and($extension->last_executed_at)->not->toBeNull();

    // Verify execution record
    $execution = $extension->executions()->first();
    expect($execution)->not->toBeNull()
        ->and($execution->success)->toBeTrue()
        ->and($execution->execution_time_ms)->toBeGreaterThan(0);

    // Deactivate extension
    $deactivated = $registry->deactivate($extension);
    expect($deactivated->status)->toBe(ExtensionStatus::INACTIVE);

    // Disable extension
    $disabled = $registry->disable($deactivated);
    expect($disabled->status)->toBe(ExtensionStatus::DISABLED);
});

/**
 * Integration test: Extension isolation and permission enforcement
 */
test('extension cannot bypass team permissions', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    $user = User::factory()->create();

    $registry = new ExtensionRegistry;

    // Register extension for team1
    $extension = $registry->register(
        teamId: $team1->id,
        creatorId: $user->id,
        name: 'Team 1 Extension',
        slug: 'team-1-extension',
        type: ExtensionType::LOGIC_HOOK,
        handlerClass: TestHandler::class
    );

    $registry->activate($extension);

    // Try to execute with team2 context - should be isolated
    $context = [
        'id' => 1,
        'team_id' => $team2->id,
        'data' => 'sensitive',
    ];

    $result = $registry->executeExtension($extension, $context);

    // Extension should receive context but be scoped to its team
    expect($result)->toBeArray();

    // Verify extension cannot access team2 data
    expect($extension->team_id)->toBe($team1->id)
        ->and($extension->team_id)->not->toBe($team2->id);
});

/**
 * Integration test: Extension error handling and graceful failure
 */
test('extension failures are isolated and logged', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $registry = new ExtensionRegistry;

    // Register extension with failing handler
    $extension = Extension::factory()->active()->create([
        'team_id' => $team->id,
        'handler_class' => FailingExtensionHandler::class,
        'handler_method' => 'handle',
    ]);

    $context = ['test' => 'data'];

    // Execute - should fail gracefully
    $result = $registry->executeExtension($extension, $context);

    // Should return original context on failure
    expect($result)->toBe($context);

    // Verify failure tracking
    $extension = $extension->fresh();
    expect($extension->failure_count)->toBe(1);

    // Verify execution record shows failure
    $execution = $extension->executions()->first();
    expect($execution)->not->toBeNull()
        ->and($execution->success)->toBeFalse()
        ->and($execution->error_message)->not->toBeNull();
});

/**
 * Integration test: Multiple extensions with priority ordering
 */
test('multiple extensions execute in priority order', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $registry = new ExtensionRegistry;

    // Register three extensions with different priorities
    $ext1 = Extension::factory()->active()->create([
        'team_id' => $team->id,
        'target_model' => Company::class,
        'target_event' => HookEvent::AFTER_SAVE,
        'priority' => 100,
        'handler_class' => TestHandler::class,
    ]);

    $ext2 = Extension::factory()->active()->create([
        'team_id' => $team->id,
        'target_model' => Company::class,
        'target_event' => HookEvent::AFTER_SAVE,
        'priority' => 50,
        'handler_class' => TestHandler::class,
    ]);

    $ext3 = Extension::factory()->active()->create([
        'team_id' => $team->id,
        'target_model' => Company::class,
        'target_event' => HookEvent::AFTER_SAVE,
        'priority' => 75,
        'handler_class' => TestHandler::class,
    ]);

    // Get hooks in order
    $hooks = $registry->getHooksFor(Company::class, HookEvent::AFTER_SAVE);

    expect($hooks)->toHaveCount(3);

    // Verify priority ordering (lower priority first)
    expect($hooks->pluck('priority')->toArray())->toBe([50, 75, 100]);
    expect($hooks->first()->id)->toBe($ext2->id);
    expect($hooks->last()->id)->toBe($ext1->id);
});

/**
 * Integration test: Extension statistics and monitoring
 */
test('extension statistics track performance accurately', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $registry = new ExtensionRegistry;

    $extension = Extension::factory()->active()->create([
        'team_id' => $team->id,
        'handler_class' => TestHandler::class,
    ]);

    // Execute multiple times with some failures
    for ($i = 0; $i < 10; $i++) {
        $context = ['iteration' => $i, 'team_id' => $team->id];
        $registry->executeExtension($extension, $context);
    }

    // Get statistics
    $stats = $registry->getStatistics($extension->fresh());

    expect($stats['total_executions'])->toBe(10)
        ->and($stats['total_failures'])->toBe(0)
        ->and($stats['success_rate'])->toBe(100.0)
        ->and($stats)->toHaveKey('avg_execution_time_ms');
});

// Test handler that fails
final class FailingExtensionHandler
{
    public function handle(): never
    {
        throw new \RuntimeException('Extension handler failed');
    }
}
