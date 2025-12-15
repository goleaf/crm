<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneResource\Pages;

use App\Filament\Resources\MilestoneResource;
use App\Filament\Resources\Pages\BaseListRecords;

final class ListMilestones extends BaseListRecords
{
    protected static string $resource = MilestoneResource::class;
}

