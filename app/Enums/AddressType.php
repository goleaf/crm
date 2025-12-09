<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AddressType: string implements HasLabel
{
    case BILLING = 'billing';
    case SHIPPING = 'shipping';
    case HEADQUARTERS = 'headquarters';
    case MAILING = 'mailing';
    case OFFICE = 'office';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::BILLING => __('enums.address_type.billing'),
            self::SHIPPING => __('enums.address_type.shipping'),
            self::HEADQUARTERS => __('enums.address_type.headquarters'),
            self::MAILING => __('enums.address_type.mailing'),
            self::OFFICE => __('enums.address_type.office'),
            self::OTHER => __('enums.address_type.other'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->getLabel()])
            ->all();
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
