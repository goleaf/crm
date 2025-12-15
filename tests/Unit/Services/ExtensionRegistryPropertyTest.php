<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Extension;
use App\Models\User;
use App\Services\ExtensionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Feature: advanced-features, Property 2: Extensibility safety
 *
 * Property: Logic hooks/extensions run within scoped context, fail gracefully, and cannot bypass permissions.
 * Validates: Requirements 2.1, 2.2
 */
final class ExtensionRegistryPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ExtensionRegistry;
    }

    /**
     * Property: Extensions run within scoped context.
     *
     * For any extension and context data, the extension should receive a scoped context
     * that includes extension metadata and excludes sensitive fields.
     */
    public function test_property_extensions_run_in_scoped_context(): void
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $extension = Extension::factory()->active()->create([
                'handler_class' => ScopedContextHandler::class,
                'handler_method' => 'handle',
                'configuration' => ['test_config' => fake()->word()],
            ]);

            $context = $this->generateRandomContext();

            $this->registry->executeExtension($extension, $context);

            $receivedContext = ScopedContextHandler::$lastContext;

            // Assert scoped context includes extension metadata
            expect($receivedContext)->toHaveKey('_extension');
            expect($receivedContext['_extension'])->toHaveKey('id');
            expect($receivedContext['_extension'])->toHaveKey('name');
            expect($receivedContext['_extension'])->toHaveKey('configuration');

            // Assert sensitive data is removed
            expect($receivedContext)->not->toHaveKey('password');
            expect($receivedContext)->not->toHaveKey('token');
            expect($receivedContext)->not->toHaveKey('secret');

            // Assert non-sensitive data is preserved
            if (isset($context['safe_data'])) {
                expect($receivedContext['safe_data'])->toBe($context['safe_data']);
            }
        }
    }

    /**
     * Property: Extensions fail gracefully.
     *
     * For any extension that throws an error, the system should return the original context
     * unchanged and log the failure without crashing.
     */
    public function test_property_extensions_fail_gracefully(): void
    {
        // Run 100 iterations with random failures
        for ($i = 0; $i < 100; $i++) {
            $extension = Extension::factory()->active()->create([
                'handler_class' => RandomFailureHandler::class,
                'handler_method' => 'handle',
            ]);

            $context = $this->generateRandomContext();
            $originalContext = $context;

            RandomFailureHandler::$shouldFail = fake()->boolean();

            $result = $this->registry->executeExtension($extension, $context);

            if (RandomFailureHandler::$shouldFail) {
                // On failure, should return original context
                expect($result)->toBe($originalContext);

                $extension->refresh();
                expect($extension->failure_count)->toBeGreaterThan(0);
            } else {
                // On success, should return modified context
                expect($result)->toBeArray();
            }
        }
    }

    /**
     * Property: Extensions cannot bypass permissions.
     *
     * For any extension with permission requirements, execution should be blocked
     * if the user lacks the required permissions.
     */
    public function test_property_extensions_cannot_bypass_permissions(): void
    {
        // Create and authenticate a user for permission checks
        $user = User::factory()->create();
        $this->actingAs($user);

        // Run 100 iterations with random permission configurations
        for ($i = 0; $i < 100; $i++) {
            $requiredPermission = 'test-permission-' . $i . '-' . fake()->word();

            $extension = Extension::factory()->active()->create([
                'handler_class' => PermissionCheckHandler::class,
                'handler_method' => 'handle',
                'permissions' => [
                    'required_permissions' => [$requiredPermission],
                ],
            ]);

            $context = $this->generateRandomContext();
            $hasPermission = fake()->boolean();

            // Mock Gate to control permission - use unique permission name per iteration
            Gate::define($requiredPermission, fn () => $hasPermission);

            PermissionCheckHandler::$wasExecuted = false;

            $result = $this->registry->executeExtension($extension, $context);

            if ($hasPermission) {
                // Should execute if permission granted
                expect(PermissionCheckHandler::$wasExecuted)->toBeTrue();
            } else {
                // Should not execute if permission denied
                expect(PermissionCheckHandler::$wasExecuted)->toBeFalse();
                // Should return context (may have _extension removed but original data preserved)
                foreach ($context as $key => $value) {
                    if ($key !== '_extension') {
                        expect($result[$key] ?? null)->toBe($value);
                    }
                }
            }
        }
    }

    /**
     * Property: Extensions respect recursion limits.
     *
     * For any extension that attempts to recursively call itself, the system should
     * prevent infinite loops by limiting recursion depth.
     */
    public function test_property_extensions_respect_recursion_limits(): void
    {
        // Run 100 iterations with different recursion attempts
        for ($i = 0; $i < 100; $i++) {
            $extension = Extension::factory()->active()->create([
                'handler_class' => RecursionTestHandler::class,
                'handler_method' => 'handle',
            ]);

            RecursionTestHandler::$registry = $this->registry;
            RecursionTestHandler::$extension = $extension;
            RecursionTestHandler::$callCount = 0;

            $context = $this->generateRandomContext();

            $this->registry->executeExtension($extension, $context);

            // Should stop after max recursion depth (3)
            expect(RecursionTestHandler::$callCount)->toBeLessThanOrEqual(4);
        }
    }

    /**
     * Property: Critical fields are preserved.
     *
     * For any extension execution, critical fields like 'id' and 'team_id' should
     * never be removed from the context, even if the extension tries to remove them.
     */
    public function test_property_critical_fields_are_preserved(): void
    {
        // Run 100 iterations with random attempts to remove critical fields
        for ($i = 0; $i < 100; $i++) {
            $extension = Extension::factory()->active()->create([
                'handler_class' => CriticalFieldRemovalHandler::class,
                'handler_method' => 'handle',
            ]);

            $id = fake()->randomNumber();
            $teamId = fake()->randomNumber();

            $context = array_merge(
                $this->generateRandomContext(),
                ['id' => $id, 'team_id' => $teamId],
            );

            CriticalFieldRemovalHandler::$shouldRemoveId = fake()->boolean();
            CriticalFieldRemovalHandler::$shouldRemoveTeamId = fake()->boolean();

            $result = $this->registry->executeExtension($extension, $context);

            // Critical fields should always be present
            expect($result)->toHaveKey('id');
            expect($result)->toHaveKey('team_id');
            expect($result['id'])->toBe($id);
            expect($result['team_id'])->toBe($teamId);
        }
    }

    /**
     * Generate random context data for testing.
     *
     * @return array<string, mixed>
     */
    private function generateRandomContext(): array
    {
        $context = [
            'safe_data' => fake()->word(),
        ];

        // Randomly add sensitive fields
        if (fake()->boolean()) {
            $context['password'] = fake()->password();
        }
        if (fake()->boolean()) {
            $context['token'] = fake()->uuid();
        }
        if (fake()->boolean()) {
            $context['secret'] = fake()->sha256();
        }

        // Add random additional fields
        for ($j = 0; $j < fake()->numberBetween(0, 5); $j++) {
            $context[fake()->word()] = fake()->word();
        }

        return $context;
    }
}

// Test handlers for property testing

final class ScopedContextHandler
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

final class RandomFailureHandler
{
    public static bool $shouldFail = false;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        if (self::$shouldFail) {
            throw new \RuntimeException('Random failure');
        }

        return array_merge($context, ['processed' => true]);
    }
}

final class PermissionCheckHandler
{
    public static bool $wasExecuted = false;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        self::$wasExecuted = true;

        return $context;
    }
}

final class RecursionTestHandler
{
    public static ?ExtensionRegistry $registry = null;

    public static ?Extension $extension = null;

    public static int $callCount = 0;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        self::$callCount++;

        if (self::$registry && self::$extension) {
            self::$registry->executeExtension(self::$extension, $context);
        }

        return $context;
    }
}

final class CriticalFieldRemovalHandler
{
    public static bool $shouldRemoveId = false;

    public static bool $shouldRemoveTeamId = false;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        if (self::$shouldRemoveId) {
            unset($context['id']);
        }
        if (self::$shouldRemoveTeamId) {
            unset($context['team_id']);
        }

        return $context;
    }
}
