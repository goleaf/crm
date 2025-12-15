<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\TimeCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                             $id
 * @property int                             $team_id
 * @property int|null                        $creator_id
 * @property int|null                        $editor_id
 * @property int|null                        $deleted_by
 * @property string                          $name
 * @property string                          $code
 * @property string|null                     $description
 * @property string|null                     $color
 * @property string|null                     $icon
 * @property bool                            $is_billable_default
 * @property float|null                      $default_billing_rate
 * @property bool                            $is_active
 * @property int                             $sort_order
 * @property string                          $creation_source
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class TimeCategory extends Model
{
    use HasCreator;
    /** @use HasFactory<TimeCategoryFactory> */
    use HasFactory;
    use HasTeam;

    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'creator_id',
        'editor_id',
        'deleted_by',
        'name',
        'code',
        'description',
        'color',
        'icon',
        'is_billable_default',
        'default_billing_rate',
        'is_active',
        'sort_order',
        'creation_source',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'is_billable_default' => 'boolean',
            'default_billing_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
