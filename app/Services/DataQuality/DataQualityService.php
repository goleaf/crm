<?php

declare(strict_types=1);

namespace App\Services\DataQuality;

use App\Enums\DataIntegrityCheckStatus;
use App\Enums\DataIntegrityCheckType;
use App\Enums\MergeJobStatus;
use App\Enums\MergeJobType;
use App\Models\BackupJob;
use App\Models\DataIntegrityCheck;
use App\Models\MergeJob;
use App\Services\DuplicateDetectionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class DataQualityService
{
    public function __construct(
        private DuplicateDetectionService $duplicateDetectionService,
        private DataIntegrityService $dataIntegrityService,
        private DataCleanupService $dataCleanupService,
        private BackupService $backupService,
    ) {}

    /**
     * Create a merge job for duplicate records.
     */
    public function createMergeJob(
        Model $primaryModel,
        Model $duplicateModel,
        MergeJobType $type,
        array $mergeRules = [],
        ?int $teamId = null,
    ): MergeJob {
        $mergeJob = MergeJob::create([
            'team_id' => $teamId ?? auth()->user()?->currentTeam?->id,
            'type' => $type,
            'primary_model_type' => $primaryModel::class,
            'primary_model_id' => $primaryModel->getKey(),
            'duplicate_model_type' => $duplicateModel::class,
            'duplicate_model_id' => $duplicateModel->getKey(),
            'status' => MergeJobStatus::PENDING,
            'merge_rules' => $mergeRules,
            'created_by' => auth()->id(),
        ]);

        // Generate merge preview
        $preview = $this->generateMergePreview($primaryModel, $duplicateModel);
        $mergeJob->update(['merge_preview' => $preview]);

        return $mergeJob;
    }

    /**
     * Process a merge job.
     */
    public function processMergeJob(MergeJob $mergeJob): bool
    {
        try {
            $mergeJob->update([
                'status' => MergeJobStatus::PROCESSING,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $primaryModel = $mergeJob->primaryModel;
            $duplicateModel = $mergeJob->duplicateModel;

            if (! $primaryModel || ! $duplicateModel) {
                throw new \Exception('Primary or duplicate model not found');
            }

            $result = DB::transaction(function () use ($mergeJob, $primaryModel, $duplicateModel): true {
                // Apply field selections if provided
                if ($mergeJob->field_selections) {
                    $this->applyFieldSelections($primaryModel, $duplicateModel, $mergeJob->field_selections);
                }

                // Transfer relationships
                $transferredRelationships = $this->transferRelationships($primaryModel, $duplicateModel, $mergeJob->type);

                // Update merge job with results
                $mergeJob->update([
                    'transferred_relationships' => $transferredRelationships,
                ]);

                // Soft delete the duplicate
                $duplicateModel->delete();

                return true;
            });

            $mergeJob->update(['status' => MergeJobStatus::COMPLETED]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Merge job failed', [
                'merge_job_id' => $mergeJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $mergeJob->update([
                'status' => MergeJobStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Run data integrity checks.
     */
    public function runIntegrityCheck(
        DataIntegrityCheckType $type,
        ?string $targetModel = null,
        array $parameters = [],
        ?int $teamId = null,
    ): DataIntegrityCheck {
        $check = DataIntegrityCheck::create([
            'team_id' => $teamId ?? auth()->user()?->currentTeam?->id,
            'type' => $type,
            'status' => DataIntegrityCheckStatus::PENDING,
            'target_model' => $targetModel,
            'check_parameters' => $parameters,
            'created_by' => auth()->id(),
        ]);

        // Process the check asynchronously or synchronously based on type
        $this->processIntegrityCheck($check);

        return $check;
    }

    /**
     * Get duplicate detection results for a model.
     */
    public function findDuplicates(
        Model $model,
        float $threshold = 60.0,
        int $limit = 10,
    ): Collection {
        if (method_exists($this->duplicateDetectionService, 'findDuplicates')) {
            return $this->duplicateDetectionService->findDuplicates($model, $threshold, $limit);
        }

        // Fallback for other model types
        return collect();
    }

    /**
     * Clean up data based on specified rules.
     */
    public function cleanupData(array $rules, ?int $teamId = null): array
    {
        return $this->dataCleanupService->cleanup($rules, $teamId);
    }

    /**
     * Create a backup job.
     */
    public function createBackup(array $config, ?int $teamId = null): BackupJob
    {
        return $this->backupService->createBackup($config, $teamId);
    }

    /**
     * Restore from a backup.
     */
    public function restoreBackup(BackupJob $backupJob): bool
    {
        return $this->backupService->restore($backupJob);
    }

    /**
     * Get data quality metrics for a team.
     */
    public function getQualityMetrics(?int $teamId = null): array
    {
        $teamId ??= auth()->user()?->currentTeam?->id;

        return [
            'merge_jobs' => [
                'total' => MergeJob::where('team_id', $teamId)->count(),
                'pending' => MergeJob::where('team_id', $teamId)->where('status', MergeJobStatus::PENDING)->count(),
                'completed' => MergeJob::where('team_id', $teamId)->where('status', MergeJobStatus::COMPLETED)->count(),
                'failed' => MergeJob::where('team_id', $teamId)->where('status', MergeJobStatus::FAILED)->count(),
            ],
            'integrity_checks' => [
                'total' => DataIntegrityCheck::where('team_id', $teamId)->count(),
                'issues_found' => DataIntegrityCheck::where('team_id', $teamId)->sum('issues_found'),
                'issues_fixed' => DataIntegrityCheck::where('team_id', $teamId)->sum('issues_fixed'),
            ],
            'backups' => [
                'total' => BackupJob::where('team_id', $teamId)->count(),
                'recent' => BackupJob::where('team_id', $teamId)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ],
        ];
    }

    /**
     * Generate merge preview for two models.
     */
    private function generateMergePreview(Model $primaryModel, Model $duplicateModel): array
    {
        $preview = [];

        // Get fillable fields for comparison
        $fillableFields = $primaryModel->getFillable();

        foreach ($fillableFields as $field) {
            $primaryValue = $primaryModel->{$field};
            $duplicateValue = $duplicateModel->{$field};

            $preview[$field] = [
                'field' => $field,
                'primary' => $this->formatValue($primaryValue),
                'duplicate' => $this->formatValue($duplicateValue),
                'recommended' => $this->recommendValue($primaryValue, $duplicateValue),
            ];
        }

        return $preview;
    }

    /**
     * Apply field selections from merge job.
     */
    private function applyFieldSelections(Model $primaryModel, Model $duplicateModel, array $fieldSelections): void
    {
        foreach ($fieldSelections as $field => $source) {
            if ($source === 'duplicate' && isset($duplicateModel->{$field})) {
                $primaryModel->{$field} = $duplicateModel->{$field};
            }
        }

        $primaryModel->save();
    }

    /**
     * Transfer relationships from duplicate to primary model.
     */
    private function transferRelationships(Model $primaryModel, Model $duplicateModel, MergeJobType $type): array
    {
        $transferred = [];

        // Define relationship mappings based on model type
        $relationshipMappings = $this->getRelationshipMappings($type);

        foreach ($relationshipMappings as $relation => $config) {
            if (method_exists($duplicateModel, $relation)) {
                $count = $this->transferRelation($primaryModel, $duplicateModel, $relation, $config);
                $transferred[$relation] = $count;
            }
        }

        return $transferred;
    }

    /**
     * Transfer a specific relationship.
     */
    private function transferRelation(Model $primaryModel, Model $duplicateModel, string $relation, array $config): int
    {
        $count = 0;
        $relatedModels = $duplicateModel->{$relation}()->get();

        foreach ($relatedModels as $relatedModel) {
            if ($config['type'] === 'hasMany') {
                $relatedModel->update([$config['foreign_key'] => $primaryModel->getKey()]);
                $count++;
            } elseif ($config['type'] === 'morphToMany') {
                if (! $primaryModel->{$relation}()->where($relatedModel->getKeyName(), $relatedModel->getKey())->exists()) {
                    $primaryModel->{$relation}()->attach($relatedModel);
                    $count++;
                }
                $duplicateModel->{$relation}()->detach($relatedModel);
            }
        }

        return $count;
    }

    /**
     * Get relationship mappings for different model types.
     */
    private function getRelationshipMappings(MergeJobType $type): array
    {
        return match ($type) {
            MergeJobType::COMPANY => [
                'people' => ['type' => 'hasMany', 'foreign_key' => 'company_id'],
                'opportunities' => ['type' => 'hasMany', 'foreign_key' => 'company_id'],
                'tasks' => ['type' => 'morphToMany'],
                'notes' => ['type' => 'morphToMany'],
            ],
            MergeJobType::CONTACT => [
                'tasks' => ['type' => 'morphToMany'],
                'notes' => ['type' => 'morphToMany'],
                'opportunities' => ['type' => 'hasMany', 'foreign_key' => 'contact_id'],
            ],
            MergeJobType::LEAD => [
                'tasks' => ['type' => 'morphToMany'],
                'notes' => ['type' => 'morphToMany'],
            ],
            default => [],
        };
    }

    /**
     * Process integrity check.
     */
    private function processIntegrityCheck(DataIntegrityCheck $check): void
    {
        $check->update([
            'status' => DataIntegrityCheckStatus::RUNNING,
            'started_at' => now(),
        ]);

        try {
            $results = $this->dataIntegrityService->runCheck($check->type, $check->target_model, $check->check_parameters);

            $check->update([
                'status' => DataIntegrityCheckStatus::COMPLETED,
                'results' => $results,
                'issues_found' => $results['issues_found'] ?? 0,
                'issues_fixed' => $results['issues_fixed'] ?? 0,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $check->update([
                'status' => DataIntegrityCheckStatus::FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Format a value for display.
     */
    private function formatValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return method_exists($value, 'label') ? $value->label() : $value->value;
        }

        return $value;
    }

    /**
     * Recommend which value to use in merge.
     */
    private function recommendValue(mixed $primaryValue, mixed $duplicateValue): string
    {
        // Prefer non-null, non-empty values
        if ($this->isValueMeaningful($primaryValue) && ! $this->isValueMeaningful($duplicateValue)) {
            return 'primary';
        }

        if (! $this->isValueMeaningful($primaryValue) && $this->isValueMeaningful($duplicateValue)) {
            return 'duplicate';
        }

        // Default to primary if both are meaningful or both are empty
        return 'primary';
    }

    /**
     * Check if a value is meaningful (not null or empty).
     */
    private function isValueMeaningful(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return ! (is_string($value) && trim($value) === '');
    }
}
