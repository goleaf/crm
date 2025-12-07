<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderFulfillmentStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case PARTIAL = 'partial';
    case FULFILLED = 'fulfilled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.order_fulfillment_status.pending'),
            self::PARTIAL => __('enums.order_fulfillment_status.partial'),
            self::FULFILLED => __('enums.order_fulfillment_status.fulfilled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PARTIAL => 'warning',
            self::FULFILLED => 'success',
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
