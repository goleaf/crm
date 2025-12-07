<?php

declare(strict_types=1);

namespace App\Enums;

enum TerritoryRole: string
{
    case OWNER = 'owner';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::OWNER => __('enums.territory_role.owner'),
            self::MEMBER => __('enums.territory_role.member'),
            self::VIEWER => __('enums.territory_role.viewer'),
        };
    }

    public function canManage(): bool
    {
        return $this === self::OWNER;
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::OWNER, self::MEMBER]);
    }

    public function canView(): bool
    {
        return true;
    }
}
