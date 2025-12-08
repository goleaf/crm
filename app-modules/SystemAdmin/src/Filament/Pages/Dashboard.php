<?php

declare(strict_types=1);

namespace Relaticle\SystemAdmin\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

final class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected ?string $heading = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getSubheading(): string | Htmlable | null
    {
        return sprintf('Welcome to %s Admin | See your stats and manage your content.', brand_name());
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('view-site')
                ->label('View Website')
                ->url(config('app.url'))
                ->icon('heroicon-o-globe-alt')
                ->color('gray')
                ->openUrlInNewTab(),
        ];
    }
}
