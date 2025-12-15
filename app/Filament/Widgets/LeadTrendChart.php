<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\LeadResource;

final class LeadTrendChart extends ChartJsTrendWidget
{
    protected static string $resource = LeadResource::class;

    protected static int $weeks = 12;
}
