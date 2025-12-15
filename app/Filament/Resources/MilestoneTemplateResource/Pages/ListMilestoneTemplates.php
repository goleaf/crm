<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneTemplateResource\Pages;

use App\Filament\Resources\MilestoneTemplateResource;
use App\Filament\Resources\Pages\BaseListRecords;

final class ListMilestoneTemplates extends BaseListRecords
{
    protected static string $resource = MilestoneTemplateResource::class;
}

