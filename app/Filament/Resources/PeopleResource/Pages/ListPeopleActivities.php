<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeopleResource\Pages;

use App\Filament\Resources\PeopleResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

final class ListPeopleActivities extends ListActivities
{
    protected static string $resource = PeopleResource::class;
}
