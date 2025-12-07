<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('enums.purchase_order_status.draft'),
            self::SENT => __('enums.purchase_order_status.sent'),
            self::RECEIVED => __('enums.purchase_order_status.received'),
            self::CANCELLED => __('enums.purchase_order_status.cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'primary',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
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
