<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExtensionType: string implements HasLabel
{
    case LOGIC_HOOK = 'logic_hook';
    case ENTRY_POINT = 'entry_point';
    case CONTROLLER = 'controller';
    case VIEW = 'view';
    case METADATA = 'metadata';
    case VARDEF = 'vardef';
    case LANGUAGE = 'language';
    case SCHEDULER = 'scheduler';
    case DASHLET = 'dashlet';
    case MODULE = 'module';
    case RELATIONSHIP = 'relationship';
    case CALCULATION = 'calculation';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOGIC_HOOK => __('enums.extension_type.logic_hook'),
            self::ENTRY_POINT => __('enums.extension_type.entry_point'),
            self::CONTROLLER => __('enums.extension_type.controller'),
            self::VIEW => __('enums.extension_type.view'),
            self::METADATA => __('enums.extension_type.metadata'),
            self::VARDEF => __('enums.extension_type.vardef'),
            self::LANGUAGE => __('enums.extension_type.language'),
            self::SCHEDULER => __('enums.extension_type.scheduler'),
            self::DASHLET => __('enums.extension_type.dashlet'),
            self::MODULE => __('enums.extension_type.module'),
            self::RELATIONSHIP => __('enums.extension_type.relationship'),
            self::CALCULATION => __('enums.extension_type.calculation'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
