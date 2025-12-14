<?php

declare(strict_types=1);

namespace App\Services\BulkOperations;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final readonly class BulkOperationService
{
    public function __construct(
        private int $batchSize = 100,
        private int $maxRecords = 1000,
    ) {}

    /**
     * Preview bulk operation to show counts and affected records
     */
    public function preview(Builder $query, string $operation, array $data = []): array
    {
        $totalCount = $query->count();

        if ($totalCount > $this->maxRecords) {
            throw new \InvalidArgumentException("Cannot perform bulk operation on more than {$this->maxRecords} records. Found {$totalCount} records.");
        }

        $sampleRecords = $query->limit(10)->get();

        return [
            'total_count' => $totalCount,
            'batch_count' => ceil($totalCount / $this->batchSize),
            'sample_records' => $sampleRecords,
            'operation' => $operation,
            'data' => $data,
        ];
    }

    /**
     * Perform bulk update operation with permission checks and batching
     */
    public function bulkUpdate(Builder $query, array $data, ?User $user = null): array
    {
        $user ??= auth()->user();
        $modelClass = $query->getModel();

        // Check if user can perform bulk updates
        if (! Gate::forUser($user)->allows('update', $modelClass)) {
            throw new \UnauthorizedAccessException('User does not have permission to perform bulk updates');
        }

        $totalCount = $query->count();

        if ($totalCount > $this->maxRecords) {
            throw new \InvalidArgumentException("Cannot perform bulk operation on more than {$this->maxRecords} records. Found {$totalCount} records.");
        }

        $processedCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $query->chunk($this->batchSize, function (Collection $records) use ($data, $user, &$processedCount, &$failedCount, &$errors): void {
                foreach ($records as $record) {
                    try {
                        // Check individual record permissions
                        if (! Gate::forUser($user)->allows('update', $record)) {
                            $failedCount++;
                            $errors[] = "No permission to update record ID: {$record->id}";
                            continue;
                        }

                        $record->update($data);
                        $processedCount++;
                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = "Failed to update record ID {$record->id}: " . $e->getMessage();
                        Log::error('Bulk update failed for record', [
                            'record_id' => $record->id,
                            'model' => $record::class,
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                        ]);
                    }
                }
            });

            // If more than 50% failed, rollback
            if ($failedCount > ($processedCount + $failedCount) / 2) {
                DB::rollBack();

                throw new \RuntimeException('Bulk update failed: More than 50% of records failed to update');
            }

            DB::commit();

            return [
                'success' => true,
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Perform bulk delete operation with permission checks and batching
     */
    public function bulkDelete(Builder $query, ?User $user = null): array
    {
        $user ??= auth()->user();
        $modelClass = $query->getModel();

        // Check if user can perform bulk deletes
        if (! Gate::forUser($user)->allows('delete', $modelClass)) {
            throw new \UnauthorizedAccessException('User does not have permission to perform bulk deletes');
        }

        $totalCount = $query->count();

        if ($totalCount > $this->maxRecords) {
            throw new \InvalidArgumentException("Cannot perform bulk operation on more than {$this->maxRecords} records. Found {$totalCount} records.");
        }

        $processedCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $query->chunk($this->batchSize, function (Collection $records) use ($user, &$processedCount, &$failedCount, &$errors): void {
                foreach ($records as $record) {
                    try {
                        // Check individual record permissions
                        if (! Gate::forUser($user)->allows('delete', $record)) {
                            $failedCount++;
                            $errors[] = "No permission to delete record ID: {$record->id}";
                            continue;
                        }

                        $record->delete();
                        $processedCount++;
                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = "Failed to delete record ID {$record->id}: " . $e->getMessage();
                        Log::error('Bulk delete failed for record', [
                            'record_id' => $record->id,
                            'model' => $record::class,
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                        ]);
                    }
                }
            });

            // If more than 50% failed, rollback
            if ($failedCount > ($processedCount + $failedCount) / 2) {
                DB::rollBack();

                throw new \RuntimeException('Bulk delete failed: More than 50% of records failed to delete');
            }

            DB::commit();

            return [
                'success' => true,
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Perform bulk assignment operation (e.g., assign tasks to users, assign records to teams)
     */
    public function bulkAssign(Builder $query, string $field, mixed $value, ?User $user = null): array
    {
        $user ??= auth()->user();
        $modelClass = $query->getModel();

        // Check if user can perform bulk updates (assignment is a form of update)
        if (! Gate::forUser($user)->allows('update', $modelClass)) {
            throw new \UnauthorizedAccessException('User does not have permission to perform bulk assignments');
        }

        return $this->bulkUpdate($query, [$field => $value], $user);
    }

    /**
     * Get operation statistics
     */
    public function getOperationStats(Builder $query): array
    {
        $totalCount = $query->count();
        $batchCount = ceil($totalCount / $this->batchSize);
        $estimatedTime = $batchCount * 2; // Rough estimate: 2 seconds per batch

        return [
            'total_records' => $totalCount,
            'batch_size' => $this->batchSize,
            'batch_count' => $batchCount,
            'estimated_time_seconds' => $estimatedTime,
            'max_records_allowed' => $this->maxRecords,
            'can_proceed' => $totalCount <= $this->maxRecords,
        ];
    }
}
