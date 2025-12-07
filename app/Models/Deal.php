<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias model for opportunities when referenced as deals.
 */
final class Deal extends Opportunity
{
    /**
     * @var string
     */
    protected $table = 'opportunities';
}
