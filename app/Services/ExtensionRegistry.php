<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExtensionStatus;
use App\Enums\ExtensionType;
use App\Enums\HookEvent;
use App\Models\Extension;
use App\Models\ExtensionExecution;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ExtensionRegistry
{
    private const int MAX_RECURSION_DEPTH = 3;

    /** @var array<string, int> */
    private array $executionDepth = [];

    /**
     * Register a new extension.
     *
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $permissions
     * @param array<string, mixed> $metadata
     */
    public function register(
        int $teamId,
        int $creatorId,
        string $name,
        string $slug,
        ExtensionType $type,
        string $handlerClass,
        ?string $description = null,
        ?string $targetModel = null,
        ?HookEvent $targetEvent = null,
        int $priority = 100,
        string $handlerMethod = 'handle',
        array $configuration = [],
        array $permissions = [],
        array $metadata = [],
    ): Extension {
        // Validate handler class exists
        if (! class_exists($handlerClass)) {
            throw new \InvalidArgumentException("Handler class {$handlerClass} does not exist");
        }

        // Validate handler method exists
        if (! method_exists($handlerClass, $handlerMethod)) {
            throw new \InvalidArgumentException("Handler method {$handlerMethod} does not exist in {$handlerClass}");
        }

        return Extension::create([
            'team_id' => $teamId,
            'creator_id' => $creatorId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'type' => $type,
            'status' => ExtensionStatus::INACTIVE,
            'priority' => $priority,
            'target_model' => $targetModel,
            'target_event' => $targetEvent,
            'handler_class' => $handlerClass,
            'handler_method' => $handlerMethod,
            'configuration' => $configuration,
            'permissions' => $permissions,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Activate an extension.
     */
    public function activate(Extension $extension): Extension
    {
        $extension->update(['status' => ExtensionStatus::ACTIVE]);

        return $extension->fresh();
    }

    /**
     * Deactivate an extension.
     */
    public function deactivate(Extension $extension): Extension
    {
        $extension->update(['status' => ExtensionStatus::INACTIVE]);

        return $extension->fresh();
    }

    /**
     * Disable an extension (prevents activation).
     */
    public function disable(Extension $extension): Extension
    {
        $extension->update(['status' => ExtensionStatus::DISABLED]);

        return $extension->fresh();
    }

    /**
     * Get active extensions for a specific hook.
     *
     * @return Collection<int, Extension>
     */
    public function getHooksFor(string $targetModel, HookEvent $event): Collection
    {
        return Extension::query()
            ->where('type', ExtensionType::LOGIC_HOOK)
            ->where('status', ExtensionStatus::ACTIVE)
            ->where('target_model', $targetModel)
            ->where('target_event', $event)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Get active extensions by type.
     *
     * @return Collection<int, Extension>
     */
    public function getByType(ExtensionType $type): Collection
    {
        return Extension::query()
            ->where('type', $type)
            ->where('status', ExtensionStatus::ACTIVE)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Execute a hook with guardrails.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function executeHook(
        string $targetModel,
        HookEvent $event,
        array $context = [],
    ): array {
        $hooks = $this->getHooksFor($targetModel, $event);

        foreach ($hooks as $hook) {
            $context = $this->executeExtension($hook, $context);
        }

        return $context;
    }

    /**
     * Execute a single extension with guardrails.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function executeExtension(Extension $extension, array $context = []): array
    {
        // Check recursion depth
        $key = $extension->id;
        $this->executionDepth[$key] = ($this->executionDepth[$key] ?? 0) + 1;

        if ($this->executionDepth[$key] > self::MAX_RECURSION_DEPTH) {
            $this->executionDepth[$key]--;
            Log::warning("Extension {$extension->slug} exceeded recursion depth limit");

            return $context;
        }

        $startTime = microtime(true);
        $userId = auth()->id();

        try {
            // Check permissions
            if (! $this->checkPermissions($extension)) {
                throw new \RuntimeException('Permission denied for extension execution');
            }

            // Create scoped context
            $scopedContext = $this->createScopedContext($extension, $context);

            // Execute with timeout
            $result = $this->executeWithTimeout(
                $extension,
                $scopedContext,
            );

            // Validate result
            $validatedResult = $this->validateResult($result, $context);

            // Log successful execution
            $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->logExecution($extension, $userId, 'success', $context, $validatedResult, $executionTimeMs);

            $extension->incrementExecutionCount();

            $this->executionDepth[$key]--;

            return $validatedResult;
        } catch (Throwable $e) {
            $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // Log failed execution
            $this->logExecution($extension, $userId, 'failed', $context, null, $executionTimeMs, $errorMessage);

            $extension->incrementFailureCount($errorMessage);

            // Disable extension if too many failures
            if ($extension->failure_count >= 10) {
                $extension->update(['status' => ExtensionStatus::FAILED]);
                Log::error("Extension {$extension->slug} disabled due to repeated failures");
            }

            Log::error("Extension {$extension->slug} execution failed: {$errorMessage}");

            $this->executionDepth[$key]--;

            // Return original context on failure (fail gracefully)
            return $context;
        }
    }

    /**
     * Check if user has permission to execute extension.
     */
    private function checkPermissions(Extension $extension): bool
    {
        $permissions = $extension->permissions ?? [];

        // If no permissions defined, allow execution
        if (empty($permissions)) {
            return true;
        }

        // Check required permissions
        if (isset($permissions['required_permissions'])) {
            foreach ($permissions['required_permissions'] as $permission) {
                if (! Gate::allows($permission)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create a scoped context for extension execution.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function createScopedContext(Extension $extension, array $context): array
    {
        // Add extension configuration to context
        $scopedContext = array_merge($context, [
            '_extension' => [
                'id' => $extension->id,
                'name' => $extension->name,
                'type' => $extension->type->value,
                'configuration' => $extension->configuration ?? [],
            ],
        ]);

        // Remove sensitive data
        unset($scopedContext['password']);
        unset($scopedContext['token']);
        unset($scopedContext['secret']);

        return $scopedContext;
    }

    /**
     * Execute extension with timeout.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function executeWithTimeout(Extension $extension, array $context): array
    {
        $handlerClass = $extension->handler_class;
        $handlerMethod = $extension->handler_method;

        // Instantiate handler
        $handler = new $handlerClass;

        // Execute handler method
        $result = $handler->{$handlerMethod}($context);

        // Ensure result is an array
        if (! is_array($result)) {
            throw new \RuntimeException('Extension handler must return an array');
        }

        return $result;
    }

    /**
     * Validate extension result.
     *
     * @param array<string, mixed> $result
     * @param array<string, mixed> $originalContext
     *
     * @return array<string, mixed>
     */
    private function validateResult(array $result, array $originalContext): array
    {
        // Remove extension metadata from result
        unset($result['_extension']);

        // Ensure critical fields are not removed
        foreach (['id', 'team_id'] as $criticalField) {
            if (isset($originalContext[$criticalField]) && ! isset($result[$criticalField])) {
                $result[$criticalField] = $originalContext[$criticalField];
            }
        }

        return $result;
    }

    /**
     * Log extension execution.
     *
     * @param array<string, mixed>|null $inputData
     * @param array<string, mixed>|null $outputData
     */
    private function logExecution(
        Extension $extension,
        ?int $userId,
        string $status,
        ?array $inputData,
        ?array $outputData,
        int $executionTimeMs,
        ?string $errorMessage = null,
    ): ExtensionExecution {
        return ExtensionExecution::create([
            'team_id' => $extension->team_id,
            'extension_id' => $extension->id,
            'user_id' => $userId,
            'status' => $status,
            'input_data' => $inputData,
            'output_data' => $outputData,
            'error_message' => $errorMessage,
            'execution_time_ms' => $executionTimeMs,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get extension statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(Extension $extension): array
    {
        $executions = $extension->executions();

        return [
            'total_executions' => $extension->execution_count,
            'total_failures' => $extension->failure_count,
            'success_rate' => $extension->execution_count > 0
                ? round((($extension->execution_count - $extension->failure_count) / $extension->execution_count) * 100, 2)
                : 0,
            'last_executed_at' => $extension->last_executed_at?->toIso8601String(),
            'avg_execution_time_ms' => $executions->avg('execution_time_ms'),
            'recent_executions' => $executions->latest()->limit(10)->get(),
        ];
    }
}
