<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneResource\Pages;

use App\Filament\Resources\MilestoneResource;
use Filament\Resources\Pages\EditRecord;

final class EditMilestone extends EditRecord
{
    protected static string $resource = MilestoneResource::class;
}

