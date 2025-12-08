<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Observers\SupportCaseObserver;
use Database\Factories\SupportCaseFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

/**
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property CaseStatus $status
 * @property CasePriority $priority
 * @property CaseType $type
 * @property CaseChannel $channel
 */
#[ObservedBy(SupportCaseObserver::class)]
final class SupportCase extends Model implements HasCustomFields
{
    use HasCreator;

    /** @use HasFactory<SupportCaseFactory> */
    use HasFactory;

    use HasNotesAndNotables;
    use HasTaxonomies;
    use HasTeam;
    use SoftDeletes;
    use UsesCustomFields;

    protected $table = 'cases';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'case_number',
        'subject',
        'description',
        'status',
        'priority',
        'type',
        'channel',
        'queue',
        'sla_due_at',
        'sla_breach_at',
        'sla_breached',
        'first_response_at',
        'response_time_minutes',
        'resolved_at',
        'resolution_time_minutes',
        'escalated_at',
        'escalation_level',
        'resolution_summary',
        'thread_reference',
        'customer_portal_url',
        'portal_visible',
        'knowledge_base_reference',
        'knowledge_article_id',
        'email_message_id',
        'company_id',
        'contact_id',
        'account_id',
        'assigned_to_id',
        'assigned_team_id',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CaseStatus::NEW,
        'priority' => CasePriority::P3,
        'type' => CaseType::QUESTION,
        'channel' => CaseChannel::INTERNAL,
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
            'status' => CaseStatus::class,
            'priority' => CasePriority::class,
            'type' => CaseType::class,
            'channel' => CaseChannel::class,
            'sla_due_at' => 'datetime',
            'sla_breach_at' => 'datetime',
            'sla_breached' => 'boolean',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'escalated_at' => 'datetime',
            'escalation_level' => 'integer',
            'portal_visible' => 'boolean',
            'response_time_minutes' => 'integer',
            'resolution_time_minutes' => 'integer',
            'creation_source' => CreationSource::class,
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
     * Account associated with this case.
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
        return $this->belongsTo(People::class, 'contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * Knowledge article linked to this case.
     *
     * @return BelongsTo<KnowledgeArticle, $this>
     */
    public function knowledgeArticle(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class);
    }

    /**
     * Check if the case has breached its SLA.
     */
    public function hasBreachedSla(): bool
    {
        return $this->sla_breached;
    }

    /**
     * Check if the case is overdue (past SLA without being resolved).
     */
    public function isOverdue(): bool
    {
        if ($this->resolved_at !== null) {
            return false;
        }

        if ($this->sla_due_at === null) {
            return false;
        }

        return now()->isAfter($this->sla_due_at);
    }

    /**
     * Get the time remaining until SLA breach (in minutes).
     */
    public function getTimeUntilSlaBreach(): ?int
    {
        if ($this->sla_due_at === null || $this->resolved_at !== null) {
            return null;
        }

        $diff = (int) now()->diffInMinutes($this->sla_due_at, false);

        return $diff > 0 ? $diff : 0;
    }
}
