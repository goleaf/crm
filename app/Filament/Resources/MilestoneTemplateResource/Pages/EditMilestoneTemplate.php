<?php

declare(strict_types=1);

namespace App\Filament\Resources\MilestoneTemplateResource\Pages;

use App\Filament\Resources\MilestoneTemplateResource;
use Filament\Resources\Pages\EditRecord;

final class EditMilestoneTemplate extends EditRecord
{
    protected static string $resource = MilestoneTemplateResource::class;
}

