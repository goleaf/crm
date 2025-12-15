<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias model for quote line items.
 */
final class QuoteProduct extends QuoteLineItem
{
    /**
     * @var string
     */
    protected $table = 'quote_line_items';
}
