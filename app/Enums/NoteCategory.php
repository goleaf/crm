<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NoteCategory: string implements HasColor, HasLabel
{
    case GENERAL = 'general';
    case CALL = 'call';
    case MEETING = 'meeting';
    case EMAIL = 'email';
    case FOLLOW_UP = 'follow_up';
    case SUPPORT = 'support';
    case TASK = 'task';
    case OTHER = 'other';

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::GENERAL => __('enums.note_category.general'),
            self::CALL => __('enums.note_category.call'),
            self::MEETING => __('enums.note_category.meeting'),
            self::EMAIL => __('enums.note_category.email'),
            self::FOLLOW_UP => __('enums.note_category.follow_up'),
            self::SUPPORT => __('enums.note_category.support'),
            self::TASK => __('enums.note_category.task'),
            self::OTHER => __('enums.note_category.other'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::GENERAL => 'gray',
            self::CALL => 'primary',
            self::MEETING => 'success',
            self::EMAIL => 'info',
            self::FOLLOW_UP => 'warning',
            self::SUPPORT => 'danger',
            self::TASK => 'secondary',
            self::OTHER => 'gray',
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

        foreach (self::cases() as $category) {
            $options[$category->value] = $category->getLabel();
        }

        return $options;
    }
}
