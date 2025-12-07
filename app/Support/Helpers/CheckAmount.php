<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use InvalidArgumentException;

final class CheckAmount
{
    /**
     * Validate and normalize a monetary amount.
     *
     * @throws InvalidArgumentException
     */
    public static function assert(mixed $amount, float $min = 0, ?float $max = null, int $precision = 2): float
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Amount must be numeric.');
        }

        $value = (float) $amount;

        if (! is_finite($value)) {
            throw new InvalidArgumentException('Amount must be a finite number.');
        }

        if ($value < $min) {
            throw new InvalidArgumentException("Amount must be at least {$min}.");
        }

        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("Amount must be at most {$max}.");
        }

        return round($value, $precision);
    }
}
