<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NoteVisibility: string implements HasColor, HasLabel
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case PRIVATE = 'private';

    public function getLabel(): string
    {
        return match ($this) {
            self::INTERNAL => __('enums.note_visibility.internal'),
            self::EXTERNAL => __('enums.note_visibility.external'),
            self::PRIVATE => __('enums.note_visibility.private'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::INTERNAL => 'primary',
            self::EXTERNAL => 'success',
            self::PRIVATE => 'warning',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return $this->getColor();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $visibility) {
            $options[$visibility->value] = $visibility->getLabel();
        }

        return $options;
    }
}
