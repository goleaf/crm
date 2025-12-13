<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTags;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use App\Observers\OpportunityObserver;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property Carbon|null    $deleted_at
 * @property CreationSource $creation_source
 * @property Carbon|null    $closed_at
 */
#[ObservedBy(OpportunityObserver::class)]
final class Opportunity extends Model implements HasCustomFields
{
    use HasCreator;

    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    use HasNotesAndNotables;
    use HasTags;
    use HasTaxonomies;
    use HasTeam;
    use LogsActivity;
    use SoftDeletes;
    use SortableTrait;
    use UsesCustomFields;

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $opportunity): void {
            $opportunity->calculateWeightedAmount();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'creation_source',
        'account_id',
        'stage',
        'probability',
        'amount',
        'weighted_amount',
        'expected_close_date',
        'competitors',
        'next_steps',
        'win_loss_reason',
        'forecast_category',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'closed_at' => 'datetime',
            'probability' => 'decimal:2',
            'amount' => 'decimal:2',
            'weighted_amount' => 'decimal:2',
            'expected_close_date' => 'date',
            'competitors' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Account associated with this opportunity.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(People::class);
    }

    /**
     * Primary owner responsible for the opportunity.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * User who closed out the opportunity (won or lost).
     *
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * Order created from this opportunity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\Order, $this>
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    /**
     * Calculate and update the weighted amount based on amount and probability.
     */
    public function calculateWeightedAmount(): void
    {
        if ($this->amount !== null && $this->probability !== null) {
            $this->weighted_amount = $this->amount * ($this->probability / 100);
        } else {
            $this->weighted_amount = null;
        }
    }

    /**
     * Check if the opportunity is in an open stage.
     */
    public function isOpen(): bool
    {
        // This would typically check against stage values
        // For now, we'll consider it open if not closed
        return $this->closed_at === null;
    }

    /**
     * Check if the opportunity is won.
     */
    public function isWon(): bool
    {
        return $this->closed_at !== null && 
               $this->win_loss_reason !== null && 
               str_contains(strtolower($this->win_loss_reason), 'won');
    }

    /**
     * Check if the opportunity is lost.
     */
    public function isLost(): bool
    {
        return $this->closed_at !== null && 
               $this->win_loss_reason !== null && 
               str_contains(strtolower($this->win_loss_reason), 'lost');
    }
}
