<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Widgets\ProjectScheduleWidget;
use App\Models\Project;
use Filament\Resources\Pages\Page;
use Filament\Widgets\WidgetConfiguration;
use Override;

/**
 * Page for viewing project schedule, critical path, and timeline.
 *
 * This page displays comprehensive project scheduling information including:
 * - Gantt chart export data
 * - Budget summary with task breakdown
 * - Critical path analysis
 * - Schedule summary widget
 *
 * **Filament v4 Compatibility:**
 * - Uses instance-level `$view` property (non-static) per v4 conventions
 * - Integrates ProjectScheduleWidget in header
 * - Leverages ProjectSchedulingService for calculations
 *
 * **Performance Considerations:**
 * - Critical path and timeline calculations are cached (15-minute TTL)
 * - Budget summary cached (10-minute TTL)
 * - Database queries optimized with indexes on project/task relationships
 * - See `docs/performance-project-schedule.md` for optimization details
 *
 * @property Project $record The project being viewed
 *
 * @see \App\Services\ProjectSchedulingService For scheduling calculations
 * @see \App\Filament\Widgets\ProjectScheduleWidget For schedule summary widget
 * @see \App\Models\Project::exportForGantt() For Gantt chart data structure
 */
final class ViewProjectSchedule extends Page
{
    /**
     * The resource this page belongs to.
     *
     * @var class-string<ProjectResource>
     */
    protected static string $resource = ProjectResource::class;

    /**
     * The Blade view to render for this page.
     *
     * Note: In Filament v4, this should be an instance property (non-static)
     * to allow for dynamic view resolution per page instance.
     */
    protected string $view = 'filament.resources.project-resource.pages.view-project-schedule';

    /**
     * The project record being displayed.
     */
    public Project $record;

    /**
     * Get the page title.
     *
     * @return string The translated page title
     */
    #[Override]
    public function getTitle(): string
    {
        return __('app.labels.project_schedule');
    }

    /**
     * Get the header widgets for this page.
     *
     * Returns the ProjectScheduleWidget configured with the current project.
     *
     * @return array<int, class-string<ProjectScheduleWidget>|WidgetConfiguration>
     */
    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ProjectScheduleWidget::make(['project' => $this->record]),
        ];
    }

    /**
     * Get the data to pass to the view.
     *
     * Prepares all necessary data for the schedule view including:
     * - Gantt chart export data with tasks, milestones, and critical path
     * - Budget summary with cost breakdown by task
     * - The project record itself
     *
     * @return array<string, mixed> View data containing project, ganttData, and budgetSummary
     */
    #[Override]
    protected function getViewData(): array
    {
        $ganttData = $this->record->exportForGantt();
        $budgetSummary = $this->record->getBudgetSummary();

        return [
            'project' => $this->record,
            'ganttData' => $ganttData,
            'budgetSummary' => $budgetSummary,
        ];
    }
}
