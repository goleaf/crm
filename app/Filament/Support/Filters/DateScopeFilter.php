<?php

declare(strict_types=1);

namespace App\Filament\Support\Filters;

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class DateScopeFilter
{
    public static function make(string $name = 'created_at_range', string $column = 'created_at'): SelectFilter
    {
        return SelectFilter::make($name)
            ->label(__('app.filters.date_range'))
            ->options([
                'today' => __('app.filters.today'),
                'last_7_days' => __('app.filters.last_7_days'),
                'last_30_days' => __('app.filters.last_30_days'),
                'this_month' => __('app.filters.this_month'),
                'last_month' => __('app.filters.last_month'),
                'this_quarter' => __('app.filters.this_quarter'),
                'this_year' => __('app.filters.this_year'),
            ])
            ->placeholder(__('app.filters.any_time'))
            ->query(fn (Builder $query, string $range): Builder => match ($range) {
                'today' => $query->ofToday(column: $column),
                'last_7_days' => $query->ofLast7Days(column: $column),
                'last_30_days' => $query->ofLast30Days(column: $column),
                'this_month' => $query->monthToDate(column: $column),
                'last_month' => $query->ofLastMonth(startFrom: now(), column: $column),
                'this_quarter' => $query->quarterToDate(column: $column),
                'this_year' => $query->yearToDate(column: $column),
                default => $query,
            })
            ->preload();
    }
}
