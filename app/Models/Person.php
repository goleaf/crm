<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias model for people when referenced as person.
 */
final class Person extends People
{
    /**
     * @var string
     */
    protected $table = 'people';
}
