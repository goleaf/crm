<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias model for companies when referenced as organisations.
 */
final class Organisation extends Company
{
    /**
     * @var string
     */
    protected $table = 'companies';
}
