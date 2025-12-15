<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\LeaveAccrualFrequency;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LeaveType extends Model
{
    use HasCreator;

    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'code',
        'description',
        'color',
        'icon',
        'is_paid',
        'requires_approval',
        'max_days_per_year',
        'accrual_rate',
        'accrual_frequency',
        'allow_carryover',
        'max_carryover_days',
        'is_active',
        'sort_order',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_paid' => true,
        'requires_approval' => true,
        'allow_carryover' => false,
        'is_active' => true,
        'sort_order' => 0,
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'max_days_per_year' => 'integer',
            'accrual_rate' => 'decimal:2',
            'accrual_frequency' => LeaveAccrualFrequency::class,
            'allow_carryover' => 'boolean',
            'max_carryover_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'creation_source' => CreationSource::class,
        ];
    }

    /**
     * @return HasMany<Absence, $this>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * @return HasMany<LeaveBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
