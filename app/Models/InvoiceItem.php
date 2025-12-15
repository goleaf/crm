<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias model for invoice line items.
 */
final class InvoiceItem extends InvoiceLineItem
{
    /**
     * @var string
     */
    protected $table = 'invoice_line_items';
}
