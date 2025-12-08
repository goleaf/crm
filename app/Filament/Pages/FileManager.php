<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

final class FileManager extends Page
{
    protected static ?string $slug = 'file-manager';

    protected static string $view = 'filament.pages.file-manager';

    protected static ?int $navigationSort = 100;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-folder';
    }

    public static function getNavigationLabel(): string
    {
        return 'File Manager';
    }

    public static function getNavigationGroup(): string
    {
        return 'System';
    }
}
