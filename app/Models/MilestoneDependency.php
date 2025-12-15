<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DependencyType;
use Database\Factories\MilestoneDependencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int            $id
 * @property int            $predecessor_id
 * @property int            $successor_id
 * @property DependencyType $dependency_type
 * @property int            $lag_days
 * @property bool           $is_active
 */
final class MilestoneDependency extends Model
{
    /** @use HasFactory<MilestoneDependencyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'predecessor_id',
        'successor_id',
        'dependency_type',
        'lag_days',
        'is_active',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'dependency_type' => DependencyType::class,
            'lag_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Milestone, $this>
     */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'predecessor_id');
    }

    /**
     * @return BelongsTo<Milestone, $this>
     */
    public function successor(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'successor_id');
    }
}

