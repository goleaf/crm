<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $input_data
 * @property array<string, mixed>|null $output_data
 */
final class ExtensionExecution extends Model
{
    use HasFactory;
    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'extension_id',
        'user_id',
        'status',
        'input_data',
        'output_data',
        'error_message',
        'execution_time_ms',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'input_data' => 'array',
            'output_data' => 'array',
            'execution_time_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Extension, $this>
     */
    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
