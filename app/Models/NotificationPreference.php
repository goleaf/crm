<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificationPreference extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'in_app',
        'email',
        'realtime',
        'activity_alerts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'in_app' => 'boolean',
            'email' => 'boolean',
            'realtime' => 'boolean',
            'activity_alerts' => 'boolean',
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
