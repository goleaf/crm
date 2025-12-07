<?php

declare(strict_types=1);

namespace App\Filament\Resources\CalendarEventResource\Pages;

use App\Filament\Resources\CalendarEventResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCalendarEvent extends CreateRecord
{
    protected static string $resource = CalendarEventResource::class;
}
