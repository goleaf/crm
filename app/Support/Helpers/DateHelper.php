<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

final class DateHelper
{
    /**
     * Format a date for human reading.
     */
    public static function humanDate(mixed $date, string $format = 'M j, Y'): ?string
    {
        if ($date === null) {
            return null;
        }

        $carbon = $date instanceof Carbon ? $date : Date::parse($date);

        return $carbon->format($format);
    }

    /**
     * Get relative time (e.g., "2 hours ago").
     */
    public static function ago(mixed $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $carbon = $date instanceof Carbon ? $date : Date::parse($date);

        return $carbon->diffForHumans();
    }

    /**
     * Check if a date is in the past.
     */
    public static function isPast(mixed $date): bool
    {
        if ($date === null) {
            return false;
        }

        $carbon = $date instanceof Carbon ? $date : Date::parse($date);

        return $carbon->isPast();
    }

    /**
     * Check if a date is in the future.
     */
    public static function isFuture(mixed $date): bool
    {
        if ($date === null) {
            return false;
        }

        $carbon = $date instanceof Carbon ? $date : Date::parse($date);

        return $carbon->isFuture();
    }

    /**
     * Check if a date is today.
     */
    public static function isToday(mixed $date): bool
    {
        if ($date === null) {
            return false;
        }

        $carbon = $date instanceof Carbon ? $date : Date::parse($date);

        return $carbon->isToday();
    }

    /**
     * Get the start of day for a date.
     */
    public static function startOfDay(mixed $date): Carbon
    {
        $carbon = $date instanceof Carbon ? $date : Date::parse($date);

        return $carbon->copy()->startOfDay();
    }

    /**
     * Get the end of day for a date.
     */
    public static function endOfDay(mixed $date): Carbon
    {
        $carbon = $date instanceof Carbon ? $date : Date::parse($date);

        return $carbon->copy()->endOfDay();
    }

    /**
     * Get date range between two dates.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public static function range(mixed $start, mixed $end): array
    {
        $startCarbon = $start instanceof Carbon ? $start : Date::parse($start);
        $endCarbon = $end instanceof Carbon ? $end : Date::parse($end);

        return [
            'start' => $startCarbon->copy()->startOfDay(),
            'end' => $endCarbon->copy()->endOfDay(),
        ];
    }

    /**
     * Get business days between two dates.
     */
    public static function businessDaysBetween(mixed $start, mixed $end): int
    {
        $startCarbon = $start instanceof Carbon ? $start : Date::parse($start);
        $endCarbon = $end instanceof Carbon ? $end : Date::parse($end);

        return $startCarbon->diffInDaysFiltered(
            fn (Carbon $date): bool => $date->isWeekday(),
            $endCarbon,
        );
    }

    /**
     * Format a date range for display.
     */
    public static function formatRange(mixed $start, mixed $end, string $format = 'M j, Y'): string
    {
        $startCarbon = $start instanceof Carbon ? $start : Date::parse($start);
        $endCarbon = $end instanceof Carbon ? $end : Date::parse($end);

        if ($startCarbon->isSameDay($endCarbon)) {
            return $startCarbon->format($format);
        }

        return $startCarbon->format($format) . ' - ' . $endCarbon->format($format);
    }
}
