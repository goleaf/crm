<?php

declare(strict_types=1);

namespace App\Services\Example;

use App\Models\People;
use App\Services\ActivityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Example action service demonstrating service container patterns.
 *
 * Action services handle business operations and should be transient (new instance per resolution).
 * Use constructor injection with readonly properties for all dependencies.
 */
final readonly class ExampleActionService
{
    public function __construct(
        private ActivityService $activityService,
        private ExampleQueryService $queryService,
    ) {}

    /**
     * Perform a complex business operation with transaction safety.
     */
    public function performComplexOperation(People $contact, array $data): array
    {
        return DB::transaction(function () use ($contact, $data): array {
            // Use injected services
            $metrics = $this->queryService->getContactMetrics($contact);

            // Perform business logic
            $contact->update($data);

            // Log activity
            $this->activityService->log(
                model: $contact,
                description: 'Contact updated via complex operation',
                properties: ['metrics' => $metrics],
            );

            Log::info('Complex operation completed', [
                'contact_id' => $contact->id,
                'metrics' => $metrics,
            ]);

            return [
                'success' => true,
                'contact' => $contact->fresh(),
                'metrics' => $metrics,
            ];
        });
    }
}
