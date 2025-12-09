<?php

declare(strict_types=1);

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;
}
