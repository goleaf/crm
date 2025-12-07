<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum AccountTeamAccessLevel: string implements HasColor, HasLabel
{
    case VIEW = 'view';
    case EDIT = 'edit';
    case MANAGE = 'manage';

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        $key = match ($this) {
            self::VIEW => 'enums.account_team_access_level.view',
            self::EDIT => 'enums.account_team_access_level.edit',
            self::MANAGE => 'enums.account_team_access_level.manage',
        };

        $label = __($key);

        return $label === $key ? Str::headline($this->value) : $label;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::VIEW => 'gray',
            self::EDIT => 'info',
            self::MANAGE => 'success',
        };
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

        foreach (self::cases() as $level) {
            $options[$level->value] = $level->getLabel();
        }

        return $options;
    }
}
