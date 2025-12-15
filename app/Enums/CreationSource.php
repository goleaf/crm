<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CreationSource: string implements HasLabel
{
    /**
     * Created through the web application interface by a user.
     * This includes records created through forms, dashboards, and
     * admin interfaces where a user directly inputs the data.
     */
    case WEB = 'web';

    /**
     * Created by automated system processes without direct user action.
     * Examples include scheduled jobs, event-triggered workflows,
     * and background processes that generate records automatically.
     */
    case SYSTEM = 'system';

    /**
     * Created through bulk data import functionality.
     * This applies to records generated when users upload files
     * (CSV, Excel, etc.) through import tools or when data is
     * migrated from another system in bulk operations.
     */
    case IMPORT = 'import';

    /**
     * Get the human-readable label for Filament UI components.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::WEB => __('enums.creation_source.web'),
            self::SYSTEM => __('enums.creation_source.system'),
            self::IMPORT => __('enums.creation_source.import'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
