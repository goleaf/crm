<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use App\Data\AddressData;
use App\Support\Addresses\AddressFormatter;

final class AddressLine
{
    /**
     * Format a single-line or multi-line address string.
     *
     * @param  AddressData|array<string, mixed>|null  $address
     */
    public static function format(AddressData|array|null $address, bool $multiline = false): string
    {
        return resolve(AddressFormatter::class)->format($address, $multiline);
    }
}
