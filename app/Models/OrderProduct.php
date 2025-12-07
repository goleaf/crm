<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias model for order line items.
 */
final class OrderProduct extends OrderLineItem
{
    /**
     * @var string
     */
    protected $table = 'order_line_items';
}
