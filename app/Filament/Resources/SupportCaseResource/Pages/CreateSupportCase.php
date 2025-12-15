<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportCaseResource\Pages;

use App\Filament\Resources\SupportCaseResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSupportCase extends CreateRecord
{
    protected static string $resource = SupportCaseResource::class;
}
