<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasLabel;

enum DependencyType: string implements HasLabel
{
    use EnumHelpers;

    case FINISH_TO_START = 'finish_to_start';
    case START_TO_START = 'start_to_start';
    case FINISH_TO_FINISH = 'finish_to_finish';
    case START_TO_FINISH = 'start_to_finish';

    public function getLabel(): string
    {
        return match ($this) {
            self::FINISH_TO_START => __('enums.dependency_type.finish_to_start'),
            self::START_TO_START => __('enums.dependency_type.start_to_start'),
            self::FINISH_TO_FINISH => __('enums.dependency_type.finish_to_finish'),
            self::START_TO_FINISH => __('enums.dependency_type.start_to_finish'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}

