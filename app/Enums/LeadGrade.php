<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum LeadGrade: string implements HasColor, HasLabel
{
    case A = 'a';
    case B = 'b';
    case C = 'c';
    case D = 'd';
    case E = 'e';
    case F = 'f';
    case UNRATED = 'unrated';

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        $key = match ($this) {
            self::A => 'enums.lead_grade.a',
            self::B => 'enums.lead_grade.b',
            self::C => 'enums.lead_grade.c',
            self::D => 'enums.lead_grade.d',
            self::E => 'enums.lead_grade.e',
            self::F => 'enums.lead_grade.f',
            self::UNRATED => 'enums.lead_grade.unrated',
        };

        $label = __($key);

        return $label === $key ? Str::upper($this->value) : $label;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::A => 'success',
            self::B => 'primary',
            self::C => 'info',
            self::D => 'warning',
            self::E => 'warning',
            self::F => 'danger',
            self::UNRATED => 'gray',
        };
    }

    /**
     * @return string|array<string>|null
     */
    public function color(): string|array|null
    {
        return $this->getColor();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $grade) {
            $options[$grade->value] = $grade->getLabel();
        }

        return $options;
    }
}
