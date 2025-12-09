<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

final class ListLeadActivities extends ListActivities
{
    protected static string $resource = LeadResource::class;
}
