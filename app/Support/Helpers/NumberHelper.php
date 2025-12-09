<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use NumberFormatter;

final class NumberHelper
{
    /**
     * Format a number as currency.
     */
    public static function currency(
        float|int|string|null $amount,
        string $currency = 'USD',
        ?string $locale = null,
    ): string {
        if ($amount === null) {
            return '—';
        }

        $locale ??= config('app.locale', 'en_US');
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency((float) $amount, $currency);
    }

    /**
     * Format a number with thousands separator.
     */
    public static function format(
        float|int|string|null $number,
        int $decimals = 0,
        ?string $decimalSeparator = null,
        ?string $thousandsSeparator = null,
    ): string {
        if ($number === null) {
            return '—';
        }

        $decimalSeparator ??= '.';
        $thousandsSeparator ??= ',';

        return number_format((float) $number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format a number as a percentage.
     */
    public static function percentage(
        float|int|string|null $number,
        int $decimals = 2,
        bool $includeSymbol = true,
    ): string {
        if ($number === null) {
            return '—';
        }

        $formatted = number_format((float) $number, $decimals);

        return $includeSymbol ? $formatted . '%' : $formatted;
    }

    /**
     * Convert bytes to human-readable format.
     */
    public static function fileSize(int|float|null $bytes, int $precision = 2): string
    {
        if ($bytes === null || $bytes < 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Abbreviate large numbers (e.g., 1000 -> 1K).
     */
    public static function abbreviate(float|int|null $number, int $precision = 1): string
    {
        if ($number === null) {
            return '—';
        }

        $number = (float) $number;

        if ($number < 1000) {
            return (string) $number;
        }

        $units = ['', 'K', 'M', 'B', 'T'];
        $power = floor(log($number, 1000));
        $power = min($power, count($units) - 1);

        $abbreviated = $number / (1000 ** $power);

        return round($abbreviated, $precision) . $units[$power];
    }

    /**
     * Clamp a number between min and max values.
     */
    public static function clamp(float|int $number, float|int $min, float|int $max): float|int
    {
        return max($min, min($max, $number));
    }

    /**
     * Check if a number is within a range.
     */
    public static function inRange(float|int $number, float|int $min, float|int $max): bool
    {
        return $number >= $min && $number <= $max;
    }

    /**
     * Format a number as ordinal (1st, 2nd, 3rd, etc.).
     */
    public static function ordinal(int $number): string
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }

        return $number . $ends[$number % 10];
    }
}
