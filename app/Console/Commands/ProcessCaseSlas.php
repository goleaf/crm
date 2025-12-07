<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CaseEscalationService;
use App\Services\CaseSlaService;
use Illuminate\Console\Command;

final class ProcessCaseSlas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cases:process-slas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process SLA breaches and escalations for support cases';

    /**
     * Execute the console command.
     */
    public function handle(CaseSlaService $slaService, CaseEscalationService $escalationService): int
    {
        $this->info('Processing case SLAs...');

        // Process SLA breaches
        $breachedCount = $slaService->processSlaBreach();
        $this->info("Marked {$breachedCount} cases as SLA breached");

        // Process escalations
        $escalatedCount = $escalationService->processEscalations();
        $this->info("Escalated {$escalatedCount} cases");

        $this->info('SLA processing complete');

        return self::SUCCESS;
    }
}
