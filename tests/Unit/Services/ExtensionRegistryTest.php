<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ExtensionStatus;
use App\Enums\ExtensionType;
use App\Enums\HookEvent;
use App\Extensions\TestHandler;
use App\Models\Extension;
use App\Models\Team;
use App\Models\User;
use App\Services\ExtensionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExtensionRegistryTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ExtensionRegistry;
    }

    public function test_can_register_extension(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $extension = $this->registry->register(
            teamId: $team->id,
            creatorId: $user->id,
            name: 'Test Extension',
            slug: 'test-extension',
            type: ExtensionType::LOGIC_HOOK,
            handlerClass: TestHandler::class,
            description: 'Test description',
            targetModel: \App\Models\Company::class,
            targetEvent: HookEvent::AFTER_SAVE,
            priority: 100,
        );

        expect($extension)->toBeInstanceOf(Extension::class);
        expect($extension->name)->toBe('Test Extension');
        expect($extension->status)->toBe(ExtensionStatus::INACTIVE);
        expect($extension->type)->toBe(ExtensionType::LOGIC_HOOK);
    }

    public function test_cannot_register_extension_with_invalid_handler_class(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Handler class NonExistentClass does not exist');

        $this->registry->register(
            teamId: $team->id,
            creatorId: $user->id,
            name: 'Test Extension',
            slug: 'test-extension',
            type: ExtensionType::LOGIC_HOOK,
            handlerClass: 'NonExistentClass',
        );
    }

    public function test_cannot_register_extension_with_invalid_handler_method(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Handler method nonExistentMethod does not exist');

        $this->registry->register(
            teamId: $team->id,
            creatorId: $user->id,
            name: 'Test Extension',
            slug: 'test-extension',
            type: ExtensionType::LOGIC_HOOK,
            handlerClass: TestHandler::class,
            handlerMethod: 'nonExistentMethod',
        );
    }

    public function test_can_activate_extension(): void
    {
        $extension = Extension::factory()->create([
            'status' => ExtensionStatus::INACTIVE,
        ]);

        $activated = $this->registry->activate($extension);

        expect($activated->status)->toBe(ExtensionStatus::ACTIVE);
    }

    public function test_can_deactivate_extension(): void
    {
        $extension = Extension::factory()->active()->create();

        $deactivated = $this->registry->deactivate($extension);

        expect($deactivated->status)->toBe(ExtensionStatus::INACTIVE);
    }

    public function test_can_disable_extension(): void
    {
        $extension = Extension::factory()->active()->create();

        $disabled = $this->registry->disable($extension);

        expect($disabled->status)->toBe(ExtensionStatus::DISABLED);
    }

    public function test_can_get_hooks_for_target(): void
    {
        $team = Team::factory()->create();

        Extension::factory()->active()->logicHook()->create([
            'team_id' => $team->id,
            'target_model' => \App\Models\Company::class,
            'target_event' => HookEvent::AFTER_SAVE,
            'priority' => 100,
        ]);

        Extension::factory()->active()->logicHook()->create([
            'team_id' => $team->id,
            'target_model' => \App\Models\Company::class,
            'target_event' => HookEvent::AFTER_SAVE,
            'priority' => 50,
        ]);

        Extension::factory()->active()->logicHook()->create([
            'team_id' => $team->id,
            'target_model' => \App\Models\Contact::class,
            'target_event' => HookEvent::AFTER_SAVE,
        ]);

        $hooks = $this->registry->getHooksFor(\App\Models\Company::class, HookEvent::AFTER_SAVE);

        expect($hooks)->toHaveCount(2);
        expect($hooks->first()->priority)->toBe(50); // Lower priority first
    }

    public function test_can_get_extensions_by_type(): void
    {
        Extension::factory()->active()->create(['type' => ExtensionType::LOGIC_HOOK]);
        Extension::factory()->active()->create(['type' => ExtensionType::CONTROLLER]);
        Extension::factory()->active()->create(['type' => ExtensionType::LOGIC_HOOK]);

        $hooks = $this->registry->getByType(ExtensionType::LOGIC_HOOK);

        expect($hooks)->toHaveCount(2);
    }

    public function test_can_execute_extension(): void
    {
        $extension = Extension::factory()->active()->create([
            'handler_class' => TestHandler::class,
            'handler_method' => 'handle',
        ]);

        $context = ['test' => 'data', 'id' => 1, 'team_id' => $extension->team_id];
        $result = $this->registry->executeExtension($extension, $context);

        expect($result)->toBeArray();
        expect($result['test'])->toBe('data');
        expect($result['id'])->toBe(1);

        $extension->refresh();
        expect($extension->execution_count)->toBe(1);
        expect($extension->failure_count)->toBe(0);
    }

    public function test_extension_execution_fails_gracefully(): void
    {
        $extension = Extension::factory()->active()->create([
            'handler_class' => FailingHandler::class,
            'handler_method' => 'handle',
        ]);

        $context = ['test' => 'data'];
        $result = $this->registry->executeExtension($extension, $context);

        // Should return original context on failure
        expect($result)->toBe($context);

        $extension->refresh();
        expect($extension->failure_count)->toBe(1);
    }

    public function test_extension_prevents_recursion(): void
    {
        $extension = Extension::factory()->active()->create([
            'handler_class' => RecursiveHandler::class,
            'handler_method' => 'handle',
        ]);

        RecursiveHandler::$registry = $this->registry;
        RecursiveHandler::$extension = $extension;

        $context = ['test' => 'data'];
        $result = $this->registry->executeExtension($extension, $context);

        expect($result)->toBeArray();
        // Should stop after max recursion depth
    }

    public function test_extension_removes_sensitive_data_from_context(): void
    {
        $extension = Extension::factory()->active()->create([
            'handler_class' => ContextInspectorHandler::class,
            'handler_method' => 'handle',
        ]);

        $context = [
            'test' => 'data',
            'password' => 'secret',
            'token' => 'abc123',
            'secret' => 'hidden',
        ];

        $this->registry->executeExtension($extension, $context);

        $lastContext = ContextInspectorHandler::$lastContext;
        expect($lastContext)->not->toHaveKey('password');
        expect($lastContext)->not->toHaveKey('token');
        expect($lastContext)->not->toHaveKey('secret');
        expect($lastContext['test'])->toBe('data');
    }

    public function test_can_get_extension_statistics(): void
    {
        $extension = Extension::factory()->withExecutions(10, 2)->create();

        $stats = $this->registry->getStatistics($extension);

        expect($stats['total_executions'])->toBe(10);
        expect($stats['total_failures'])->toBe(2);
        expect($stats['success_rate'])->toBe(80.0);
    }
}

// Test handlers for specific scenarios

final class FailingHandler
{
    public function handle(): never
    {
        throw new \RuntimeException('Handler failed');
    }
}

final class RecursiveHandler
{
    public static ?ExtensionRegistry $registry = null;

    public static ?Extension $extension = null;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        if (self::$registry && self::$extension) {
            self::$registry->executeExtension(self::$extension, $context);
        }

        return $context;
    }
}

final class ContextInspectorHandler
{
    /** @var array<string, mixed>|null */
    public static ?array $lastContext = null;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        self::$lastContext = $context;

        return $context;
    }
}
