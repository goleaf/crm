<?php

declare(strict_types=1);

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

final class Studio extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.studio');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.customization');
    }
}
