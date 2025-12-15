<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BulkOperationType: string implements HasColor, HasIcon, HasLabel
{
    case UPDATE = 'update';
    case DELETE = 'delete';
    case ASSIGN = 'assign';

    public function getLabel(): string
    {
        return match ($this) {
            self::UPDATE => __('enums.bulk_operation_type.update'),
            self::DELETE => __('enums.bulk_operation_type.delete'),
            self::ASSIGN => __('enums.bulk_operation_type.assign'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::UPDATE => 'warning',
            self::DELETE => 'danger',
            self::ASSIGN => 'success',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::UPDATE => 'heroicon-o-pencil-square',
            self::DELETE => 'heroicon-o-trash',
            self::ASSIGN => 'heroicon-o-user-plus',
        };
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
