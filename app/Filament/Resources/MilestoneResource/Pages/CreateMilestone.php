<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneResource\Pages;

use App\Filament\Resources\MilestoneResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMilestone extends CreateRecord
{
    protected static string $resource = MilestoneResource::class;
}

