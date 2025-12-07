<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceReminderType: string implements HasColor, HasLabel
{
    case DUE_SOON = 'due_soon';
    case OVERDUE = 'overdue';
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::DUE_SOON => __('enums.invoice_reminder_type.due_soon'),
            self::OVERDUE => __('enums.invoice_reminder_type.overdue'),
            self::CUSTOM => __('enums.invoice_reminder_type.custom'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DUE_SOON => 'info',
            self::OVERDUE => 'danger',
            self::CUSTOM => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = ${$type}->getLabel();
        }

        return $options;
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return $this->getColor();
    }
}
