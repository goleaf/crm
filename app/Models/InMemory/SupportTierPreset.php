<?php

declare(strict_types=1);

namespace App\Models\InMemory;

use App\Models\InMemoryModel;
use Waad\Truffle\Enums\DataType;

/**
 * Static support tiers for defaults and quote helpers (in-memory SQLite via Truffle).
 */
final class SupportTierPreset extends InMemoryModel
{
    protected $table = 'support_tier_presets';

    protected array $records = [
        [
            'id' => 1,
            'slug' => 'standard',
            'name' => 'Standard',
            'response_minutes' => 240,
            'max_projects' => 3,
            'channels' => ['email'],
        ],
        [
            'id' => 2,
            'slug' => 'priority',
            'name' => 'Priority',
            'response_minutes' => 120,
            'max_projects' => 10,
            'channels' => ['email', 'chat'],
        ],
        [
            'id' => 3,
            'slug' => 'enterprise',
            'name' => 'Enterprise',
            'response_minutes' => 30,
            'max_projects' => 50,
            'channels' => ['email', 'chat', 'phone'],
        ],
    ];

    protected array $schema = [
        'id' => DataType::Id,
        'slug' => DataType::String,
        'name' => DataType::String,
        'response_minutes' => DataType::Integer,
        'max_projects' => DataType::Integer,
        'channels' => DataType::Json,
    ];

    protected $casts = [
        'channels' => 'array',
    ];
}
