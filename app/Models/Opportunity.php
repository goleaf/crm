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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'creation_source',
        'account_id',
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
}
