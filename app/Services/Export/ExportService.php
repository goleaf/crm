<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\ExportJob;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final readonly class ExportService
{
    public function __construct(
        private ExportTemplateService $templateService,
        private ExportProcessorService $processorService,
        private ExportFilterService $filterService,
    ) {}

    /**
     * Create a new export job
     */
    public function createExportJob(array $config): ExportJob
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        return DB::transaction(function () use ($config, $user, $team) {
            $exportJob = ExportJob::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'name' => $config['name'],
                'model_type' => $config['model_type'],
                'format' => $config['format'] ?? 'csv',
                'template_config' => $config['template_config'] ?? null,
                'selected_fields' => $config['selected_fields'] ?? null,
                'filters' => $config['filters'] ?? null,
                'options' => $config['options'] ?? null,
                'scope' => $config['scope'] ?? 'all',
                'record_ids' => $config['record_ids'] ?? null,
                'expires_at' => now()->addDays(7), // Default 7 days expiration
            ]);

            Log::channel('exports')->info('Export job created', [
                'export_job_id' => $exportJob->id,
                'model_type' => $exportJob->model_type,
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);

            return $exportJob;
        });
    }

    /**
     * Process an export job
     */
    public function processExportJob(ExportJob $exportJob): bool
    {
        try {
            $exportJob->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Get the model class
            $modelClass = $this->getModelClass($exportJob->model_type);
            if (! $modelClass) {
                throw new \InvalidArgumentException("Invalid model type: {$exportJob->model_type}");
            }

            // Build the query
            $query = $this->buildExportQuery($modelClass, $exportJob);

            // Count total records
            $totalRecords = $query->count();
            $exportJob->update(['total_records' => $totalRecords]);

            if ($totalRecords === 0) {
                $exportJob->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                return true;
            }

            // Process the export
            $result = $this->processorService->processExport($exportJob, $query);

            $exportJob->update([
                'status' => $result['success'] ? 'completed' : 'failed',
                'processed_records' => $result['processed'],
                'successful_records' => $result['successful'],
                'failed_records' => $result['failed'],
                'file_path' => $result['file_path'] ?? null,
                'file_size' => $result['file_size'] ?? null,
                'errors' => $result['errors'] ?? null,
                'error_message' => $result['error_message'] ?? null,
                'completed_at' => now(),
            ]);

            Log::channel('exports')->info('Export job completed', [
                'export_job_id' => $exportJob->id,
                'status' => $exportJob->status,
                'total_records' => $totalRecords,
                'successful_records' => $result['successful'],
            ]);

            return $result['success'];

        } catch (\Exception $e) {
            Log::channel('exports')->error('Export job failed', [
                'export_job_id' => $exportJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $exportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return false;
        }
    }

    /**
     * Get available export templates for a model type
     */
    public function getAvailableTemplates(string $modelType, Team $team): Collection
    {
        return $this->templateService->getTemplatesForModel($modelType, $team);
    }

    /**
     * Get available fields for export for a model type
     */
    public function getAvailableFields(string $modelType): array
    {
        return $this->templateService->getAvailableFields($modelType);
    }

    /**
     * Get export jobs for a team
     */
    public function getExportJobs(Team $team, array $filters = []): Collection
    {
        $query = ExportJob::where('team_id', $team->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->get();
    }

    /**
     * Clean up expired export files
     */
    public function cleanupExpiredExports(): int
    {
        $expiredJobs = ExportJob::where('expires_at', '<', now())
            ->whereNotNull('file_path')
            ->get();

        $cleanedCount = 0;

        foreach ($expiredJobs as $job) {
            try {
                if (Storage::disk($job->file_disk)->exists($job->file_path)) {
                    Storage::disk($job->file_disk)->delete($job->file_path);
                }

                $job->update([
                    'file_path' => null,
                    'file_size' => null,
                ]);

                $cleanedCount++;
            } catch (\Exception $e) {
                Log::channel('exports')->warning('Failed to cleanup expired export file', [
                    'export_job_id' => $job->id,
                    'file_path' => $job->file_path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('exports')->info('Cleaned up expired export files', ['count' => $cleanedCount]);

        return $cleanedCount;
    }

    /**
     * Build export query based on job configuration
     */
    private function buildExportQuery(string $modelClass, ExportJob $exportJob): Builder
    {
        /** @var Builder $query */
        $query = $modelClass::query();

        // Apply team scoping
        if (method_exists($modelClass, 'scopeForTeam')) {
            $query->forTeam($exportJob->team);
        } elseif (in_array('team_id', (new $modelClass)->getFillable())) {
            $query->where('team_id', $exportJob->team_id);
        }

        // Apply scope-based filtering
        switch ($exportJob->scope) {
            case 'selected':
                if ($exportJob->record_ids) {
                    $query->whereIn('id', $exportJob->record_ids);
                }
                break;
            case 'filtered':
                if ($exportJob->filters) {
                    $query = $this->filterService->applyFilters($query, $exportJob->filters);
                }
                break;
            case 'all':
            default:
                // No additional filtering
                break;
        }

        return $query;
    }

    /**
     * Get model class from model type string
     */
    private function getModelClass(string $modelType): ?string
    {
        $modelMap = [
            'Company' => \App\Models\Company::class,
            'People' => \App\Models\People::class,
            'Opportunity' => \App\Models\Opportunity::class,
            'Task' => \App\Models\Task::class,
            'Note' => \App\Models\Note::class,
            'Lead' => \App\Models\Lead::class,
            'SupportCase' => \App\Models\SupportCase::class,
            'Customer' => \App\Models\Customer::class,
        ];

        return $modelMap[$modelType] ?? null;
    }
}
