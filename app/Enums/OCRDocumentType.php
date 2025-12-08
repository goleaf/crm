<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OCRDocumentType: string implements HasColor, HasLabel
{
    case INVOICE = 'invoice';
    case RECEIPT = 'receipt';
    case BUSINESS_CARD = 'business_card';
    case CONTRACT = 'contract';
    case SHIPPING_LABEL = 'shipping_label';
    case ID_CARD = 'id_card';
    case PASSPORT = 'passport';
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::INVOICE => __('enums.ocr_document_type.invoice'),
            self::RECEIPT => __('enums.ocr_document_type.receipt'),
            self::BUSINESS_CARD => __('enums.ocr_document_type.business_card'),
            self::CONTRACT => __('enums.ocr_document_type.contract'),
            self::SHIPPING_LABEL => __('enums.ocr_document_type.shipping_label'),
            self::ID_CARD => __('enums.ocr_document_type.id_card'),
            self::PASSPORT => __('enums.ocr_document_type.passport'),
            self::CUSTOM => __('enums.ocr_document_type.custom'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::INVOICE => 'success',
            self::RECEIPT => 'info',
            self::BUSINESS_CARD => 'warning',
            self::CONTRACT => 'danger',
            self::SHIPPING_LABEL => 'primary',
            self::ID_CARD => 'gray',
            self::PASSPORT => 'gray',
            self::CUSTOM => 'gray',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }
}
