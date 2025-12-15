<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Override;

/**
 * Widget displaying project schedule summary including critical path,
 * task progress, and schedule status.
 *
 * This widget provides a comprehensive overview of project scheduling metrics:
 * - Schedule summary with task counts and completion status
 * - Critical path tasks that determine project duration
 * - Timeline with milestones and scheduled dates
 * - Schedule health indicators (on track, at risk, blocked tasks)
 *
 * @property Project|null $project The project to display schedule information for
 */
final class ProjectScheduleWidget extends Widget
{
    /**
     * The Blade view to render for this widget.
     */
    protected string $view = 'filament.widgets.project-schedule-widget';

    /**
     * The column span for this widget (full width).
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * The project to display schedule information for.
     * Set this property before rendering the widget.
     */
    public ?Project $project = null;

    /**
     * Get the data to pass to the widget view.
     *
     * If no project is set, returns empty/null values.
     * Otherwise, fetches schedule summary, critical path, and timeline data.
     *
     * @return array<string, mixed> View data containing:
     *                              - summary: Schedule metrics (task counts, critical path length, etc.)
     *                              - criticalPath: Collection of critical path tasks
     *                              - timeline: Timeline data with milestones and scheduled dates
     *                              - project: The project instance
     */
    #[Override]
    protected function getViewData(): array
    {
        if ($this->project === null) {
            return [
                'summary' => null,
                'criticalPath' => collect(),
                'timeline' => null,
            ];
        }

        $summary = $this->project->getScheduleSummary();
        $criticalPath = $this->project->getCriticalPath();
        $timeline = $this->project->getTimeline();

        return [
            'summary' => $summary,
            'criticalPath' => $criticalPath,
            'timeline' => $timeline,
            'project' => $this->project,
        ];
    }

    /**
     * Determine if the widget can be viewed.
     *
     * @return bool True if user is authenticated
     */
    public static function canView(): bool
    {
        return Auth::check();
    }
}
