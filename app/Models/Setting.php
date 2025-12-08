<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * System settings model for managing application configuration.
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property string $type
 * @property string $group
 * @property string|null $description
 * @property bool $is_public
 * @property bool $is_encrypted
 * @property int|null $team_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
        'is_encrypted',
        'team_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get the team that owns the setting.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the setting value with proper type casting.
     */
    public function getValue(): mixed
    {
        $value = $this->is_encrypted ? Crypt::decryptString($this->value) : $this->value;

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode((string) $value, true),
            'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Set the setting value with proper type handling.
     */
    public function setValue(mixed $value): void
    {
        $stringValue = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };

        $this->value = $this->is_encrypted ? Crypt::encryptString($stringValue) : $stringValue;
    }
}
