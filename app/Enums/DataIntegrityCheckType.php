<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DataIntegrityCheckType: string implements HasColor, HasIcon, HasLabel
{
    case ORPHANED_RECORDS = 'orphaned_records';
    case MISSING_RELATIONSHIPS = 'missing_relationships';
    case DUPLICATE_DETECTION = 'duplicate_detection';
    case DATA_VALIDATION = 'data_validation';
    case FOREIGN_KEY_CONSTRAINTS = 'foreign_key_constraints';
    case REQUIRED_FIELDS = 'required_fields';
    case DATA_CONSISTENCY = 'data_consistency';

    public function getLabel(): string
    {
        return match ($this) {
            self::ORPHANED_RECORDS => __('enums.data_integrity_check_type.orphaned_records'),
            self::MISSING_RELATIONSHIPS => __('enums.data_integrity_check_type.missing_relationships'),
            self::DUPLICATE_DETECTION => __('enums.data_integrity_check_type.duplicate_detection'),
            self::DATA_VALIDATION => __('enums.data_integrity_check_type.data_validation'),
            self::FOREIGN_KEY_CONSTRAINTS => __('enums.data_integrity_check_type.foreign_key_constraints'),
            self::REQUIRED_FIELDS => __('enums.data_integrity_check_type.required_fields'),
            self::DATA_CONSISTENCY => __('enums.data_integrity_check_type.data_consistency'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ORPHANED_RECORDS => 'red',
            self::MISSING_RELATIONSHIPS => 'orange',
            self::DUPLICATE_DETECTION => 'yellow',
            self::DATA_VALIDATION => 'blue',
            self::FOREIGN_KEY_CONSTRAINTS => 'purple',
            self::REQUIRED_FIELDS => 'green',
            self::DATA_CONSISTENCY => 'indigo',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ORPHANED_RECORDS => 'heroicon-o-trash',
            self::MISSING_RELATIONSHIPS => 'heroicon-o-link-slash',
            self::DUPLICATE_DETECTION => 'heroicon-o-document-duplicate',
            self::DATA_VALIDATION => 'heroicon-o-shield-check',
            self::FOREIGN_KEY_CONSTRAINTS => 'heroicon-o-key',
            self::REQUIRED_FIELDS => 'heroicon-o-exclamation-triangle',
            self::DATA_CONSISTENCY => 'heroicon-o-check-badge',
        };
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
