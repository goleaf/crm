<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use App\Filament\Widgets\ResourceStatusChart;
use App\Filament\Widgets\ResourceTrendChart;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class BaseListRecords extends ListRecords
{
    protected function getChartDateColumn(): string
    {
        return 'created_at';
    }

    /**
     * @return array<int, string>
     */
    protected function getChartStatusCandidates(): array
    {
        return [
            'status',
            'creation_source',
            'category',
            'visibility',
            'type',
            'priority',
            'channel',
        ];
    }

    protected function getChartCacheSeconds(): int
    {
        return 600;
    }

    protected function getTrendWeeks(): int
    {
        return 8;
    }

    /**
     * @return array<class-string | \Filament\Widgets\WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        $resourceClass = static::$resource;

        $modelClass = $resourceClass::getModel();
        $model = resolve($modelClass);
        $connection = $model->getConnectionName();
        $table = $model->getTable();

        $widgets = [];

        $dateColumn = $this->resolveDateColumn($connection, $table);

        if ($dateColumn) {
            $widgets[] = ResourceTrendChart::make([
                'resourceClass' => $resourceClass,
                'dateColumn' => $dateColumn,
                'weeks' => $this->getTrendWeeks(),
                'cacheSeconds' => $this->getChartCacheSeconds(),
            ]);
        }

        if ($statusColumn = $this->resolveStatusColumn($connection, $table)) {
            $widgets[] = ResourceStatusChart::make([
                'resourceClass' => $resourceClass,
                'statusColumn' => $statusColumn,
                'cacheSeconds' => $this->getChartCacheSeconds(),
            ]);
        }

        return $widgets;
    }

    protected function resolveStatusColumn(?string $connection, string $table): ?string
    {
        foreach ($this->getChartStatusCandidates() as $column) {
            if (! $column) {
                continue;
            }
            if (Str::endsWith($column, '_id')) {
                continue;
            }
            if ($this->hasColumn($connection, $table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function resolveDateColumn(?string $connection, string $table): ?string
    {
        foreach ($this->getChartDateCandidates() as $column) {
            if ($column && $this->hasColumn($connection, $table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function hasColumn(?string $connection, string $table, string $column): bool
    {
        return Schema::connection($connection ?? config('database.default'))->hasColumn($table, $column);
    }

    /**
     * @return array<int, string>
     */
    protected function getChartDateCandidates(): array
    {
        return array_values(array_unique([
            $this->getChartDateColumn(),
            'updated_at',
        ]));
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return __('app.empty_states.heading', [
            'label' => static::getResource()::getPluralModelLabel(),
        ]);
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __('app.empty_states.description', [
            'label' => static::getResource()::getModelLabel(),
        ]);
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return Heroicon::OutlinedDocumentPlus;
    }

    /**
     * @return array<Action>
     */
    protected function getTableEmptyStateActions(): array
    {
        $action = $this->getEmptyStateCreateAction();

        if (! $action instanceof \Filament\Actions\Action) {
            return [];
        }

        $action->label($action->getLabel() ?? __('app.empty_states.action', [
            'label' => static::getResource()::getModelLabel(),
        ]));

        return [
            $action
                ->icon(Heroicon::Plus)
                ->color('primary'),
        ];
    }

    protected function getEmptyStateCreateAction(): ?Action
    {
        foreach ($this->getHeaderActions() as $action) {
            if (! $action instanceof Action) {
                continue;
            }
            if ($action instanceof CreateAction === false && $action->getName() !== 'create') {
                continue;
            }

            return clone $action;
        }

        return null;
    }
}
