<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderReceiptType: string implements HasLabel
{
    case RECEIPT = 'receipt';
    case RETURN = 'return';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEIPT => __('enums.purchase_order_receipt_type.receipt'),
            self::RETURN => __('enums.purchase_order_receipt_type.return'),
        };
    }

    public function multiplier(): int
    {
        return $this === self::RETURN ? -1 : 1;
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
