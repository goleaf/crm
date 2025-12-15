<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\GitHub\GitHubIssuesService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

final class GithubIssuesWidget extends Widget
{
    protected string $view = 'filament.widgets.github-issues-widget';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getIssues(): Collection
    {
        return resolve(GitHubIssuesService::class)->getOpenIssues(5);
    }
}
