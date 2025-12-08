<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Knowledge\ApprovalStatus;
use App\Models\Concerns\HasTeam;
use App\Observers\KnowledgeArticleApprovalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $due_at
 * @property Carbon|null $decided_at
 * @property ApprovalStatus $status
 */
#[ObservedBy(KnowledgeArticleApprovalObserver::class)]
final class KnowledgeArticleApproval extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'team_id',
        'requested_by_id',
        'approver_id',
        'status',
        'due_at',
        'decided_at',
        'decision_notes',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'due_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<KnowledgeArticle, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'article_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }
}
