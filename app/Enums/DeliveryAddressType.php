<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeliveryAddressType: string implements HasLabel
{
    case ORIGIN = 'origin';
    case DESTINATION = 'destination';
    case PICKUP = 'pickup';
    case DROP_OFF = 'drop_off';
    case RETURN = 'return';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::ORIGIN => __('enums.delivery_address_type.origin'),
            self::DESTINATION => __('enums.delivery_address_type.destination'),
            self::PICKUP => __('enums.delivery_address_type.pickup'),
            self::DROP_OFF => __('enums.delivery_address_type.drop_off'),
            self::RETURN => __('enums.delivery_address_type.return'),
            self::OTHER => __('enums.delivery_address_type.other'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
