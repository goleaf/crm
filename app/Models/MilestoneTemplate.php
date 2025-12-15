<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\MilestoneTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                            $id
 * @property int                            $team_id
 * @property string                         $name
 * @property string|null                    $description
 * @property string|null                    $category
 * @property array<string, mixed>           $template_data
 * @property int                            $usage_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class MilestoneTemplate extends Model
{
    /** @use HasFactory<MilestoneTemplateFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'description',
        'category',
        'template_data',
        'usage_count',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'template_data' => 'array',
            'usage_count' => 'integer',
        ];
    }
}

