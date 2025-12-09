<?php

declare(strict_types=1);

namespace App\Models;

use Waad\Truffle\Truffle;

/**
 * Base class for static datasets stored in an in-memory SQLite connection via Truffle.
 */
abstract class InMemoryModel extends Model
{
    use Truffle;

    public $timestamps = false;

    /**
     * Seed data for the in-memory table.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $records = [];

    /**
     * Explicit schema definition. Leave empty to infer from the first record.
     *
     * @var array<string, \Waad\Truffle\Enums\DataType>
     */
    protected array $schema = [];

    /**
     * Number of records to insert per chunk during bootstrapping.
     */
    protected int $insertChunkRecords = 500;

    /**
     * Rebuild the in-memory connection and reseed records (useful in tests when records are mutated).
     */
    public static function rebuildInMemoryData(): void
    {
        static::clearConnections();

        (new static())->migrate();
    }
}
