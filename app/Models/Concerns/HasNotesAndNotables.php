<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Compose note relations from HasNotes + notable entries while resolving method collisions.
 */
trait HasNotesAndNotables
{
    use HasNotableEntries {
        addNotableNote as addNotableEntry;
    }
    use HasNotes;
}
