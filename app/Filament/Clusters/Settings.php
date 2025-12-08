<?php

declare(strict_types=1);

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

final class Settings extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.settings');
    }
}
