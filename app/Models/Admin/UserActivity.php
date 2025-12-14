<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forModel($query, string $modelType, ?int $modelId = null)
    {
        $query = $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function recent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public static function log(string $action, $model = null, array $properties = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->id,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
