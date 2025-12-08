<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Mansoor\FilamentUnsplashPicker\Forms\Components\UnsplashPickerField as BaseUnsplashPickerField;

final class UnsplashPicker extends BaseUnsplashPickerField
{
    // This wrapper allows us to customize default behaviors or add hooks
    // consistent with the project standards if needed.
    // For now, it simply extends the package component.

    // We can also override 'make' to set defaults from config.

    // public static function make(string $name): static
    // {
    //     $static = parent::make($name);
    //     $static->imageSize(config('unsplash.defaults.quality', 'regular'));
    //     return $static;
    // }
}
