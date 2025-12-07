<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias model for tags when referenced as labels.
 */
final class Label extends Tag
{
    /**
     * @var string
     */
    protected $table = 'tags';
}
