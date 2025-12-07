<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceRecurrenceFrequency: string implements HasLabel
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public function getLabel(): string
    {
        return match ($this) {
            self::WEEKLY => __('enums.invoice_recurrence_frequency.weekly'),
            self::MONTHLY => __('enums.invoice_recurrence_frequency.monthly'),
            self::QUARTERLY => __('enums.invoice_recurrence_frequency.quarterly'),
            self::YEARLY => __('enums.invoice_recurrence_frequency.yearly'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $frequency) {
            $options[$frequency->value] = ${$frequency}->getLabel();
        }

        return $options;
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
