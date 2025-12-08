<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Stephenjude\FilamentFeatureFlag\Models\FeatureSegment as BaseFeatureSegment;

final class FeatureFlagSegment extends BaseFeatureSegment
{
    protected $table = 'feature_segments';

    protected static function booted(): void
    {
        self::addGlobalScope('current-tenant-segments', function (Builder $builder): void {
            $tenantId = Filament::getTenant()?->getKey()
                ?? Filament::auth()->user()?->currentTeam?->getKey();

            if ($tenantId === null) {
                return;
            }

            $builder->whereJsonContains('values', $tenantId);
        });
    }
}
