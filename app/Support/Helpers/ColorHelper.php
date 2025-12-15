<?php

declare(strict_types=1);

namespace App\Support\Helpers;

final class ColorHelper
{
    /**
     * Determine if a hex color is light.
     */
    public static function isLight(string $hex): bool
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $yiq >= 128;
    }

    /**
     * Determine if a hex color is dark.
     */
    public static function isDark(string $hex): bool
    {
        return ! self::isLight($hex);
    }

    /**
     * Convert hex color to RGB array.
     *
     * @return array{r: int, g: int, b: int}
     */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Convert RGB to hex color.
     */
    public static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Lighten a hex color by a percentage.
     */
    public static function lighten(string $hex, int $percent): string
    {
        $rgb = self::hexToRgb($hex);

        $r = min(255, $rgb['r'] + (int) (($percent / 100) * 255));
        $g = min(255, $rgb['g'] + (int) (($percent / 100) * 255));
        $b = min(255, $rgb['b'] + (int) (($percent / 100) * 255));

        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Darken a hex color by a percentage.
     */
    public static function darken(string $hex, int $percent): string
    {
        $rgb = self::hexToRgb($hex);

        $r = max(0, $rgb['r'] - (int) (($percent / 100) * 255));
        $g = max(0, $rgb['g'] - (int) (($percent / 100) * 255));
        $b = max(0, $rgb['b'] - (int) (($percent / 100) * 255));

        return self::rgbToHex($r, $g, $b);
    }

    /**
     * Get contrasting text color (black or white) for a background color.
     */
    public static function contrastText(string $hex): string
    {
        return self::isLight($hex) ? '#000000' : '#ffffff';
    }

    /**
     * Validate if a string is a valid hex color.
     */
    public static function isValidHex(string $hex): bool
    {
        return (bool) preg_match('/^#?([a-f0-9]{6}|[a-f0-9]{3})$/i', $hex);
    }

    /**
     * Generate a random hex color.
     */
    public static function random(): string
    {
        return sprintf('#%06x', mt_rand(0, 0xFFFFFF));
    }
}
