<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string                    $query
 * @property string|null               $module
 * @property array<string, mixed>|null $filters
 * @property int                       $results_count
 * @property float|null                $execution_time
 * @property \Carbon\Carbon            $searched_at
 */
final class SearchHistory extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'query',
        'module',
        'filters',
        'results_count',
        'execution_time',
        'searched_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'results_count' => 'integer',
            'execution_time' => 'float',
            'searched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
