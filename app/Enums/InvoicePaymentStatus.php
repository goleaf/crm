<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoicePaymentStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.invoice_payment_status.pending'),
            self::COMPLETED => __('enums.invoice_payment_status.completed'),
            self::FAILED => __('enums.invoice_payment_status.failed'),
            self::REFUNDED => __('enums.invoice_payment_status.refunded'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'warning',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = ${$status}->getLabel();
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
