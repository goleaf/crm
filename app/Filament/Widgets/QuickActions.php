<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\OpportunitiesBoard;
use App\Filament\Pages\TasksBoard;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\NoteResource;
use Filament\Widgets\Widget;

final class QuickActions extends Widget
{
    protected int|string|array $columnSpan = 1;

    /**
     * @var view-string
     */
    protected string $view = 'filament.app.widgets.quick-actions';

    /**
     * @return array<int, array<string, string>>
     */
    private function getActions(): array
    {
        return [
            [
                'label' => 'Leads',
                'description' => 'Review and qualify inbound leads.',
                'icon' => 'heroicon-o-user-plus',
                'url' => LeadResource::getUrl(),
            ],
            [
                'label' => 'Opportunities board',
                'description' => 'Track deals through each stage.',
                'icon' => 'heroicon-o-view-columns',
                'url' => OpportunitiesBoard::getUrl(),
            ],
            [
                'label' => 'Tasks board',
                'description' => 'Focus the team on what is next.',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => TasksBoard::getUrl(),
            ],
            [
                'label' => 'Companies',
                'description' => 'Manage accounts and relationships.',
                'icon' => 'heroicon-o-building-office',
                'url' => CompanyResource::getUrl(),
            ],
            [
                'label' => 'Notes',
                'description' => 'Capture updates across records.',
                'icon' => 'heroicon-o-document-text',
                'url' => NoteResource::getUrl(),
            ],
        ];
    }

    /**
     * @return array{actions: array<int, array<string, string>>}
     */
    protected function getViewData(): array
    {
        return [
            'actions' => $this->getActions(),
        ];
    }
}
