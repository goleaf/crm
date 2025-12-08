<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactEmailType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PeopleEmail extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'people_id',
        'email',
        'type',
        'is_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'type' => ContactEmailType::class,
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (PeopleEmail $email): void {
            if ($email->is_primary !== true && ! $email->alreadyHasPrimary()) {
                $email->is_primary = true;
            }
        });

        self::saving(function (PeopleEmail $email): void {
            if ($email->people_id === null) {
                return;
            }

            if ($email->is_primary === true) {
                $email->newQuery()
                    ->where('people_id', $email->people_id)
                    ->whereKeyNot($email->getKey() ?? 0)
                    ->update(['is_primary' => false]);
            }
        });

        self::saved(function (PeopleEmail $email): void {
            $email->people?->syncEmailColumns();
        });

        self::deleted(function (PeopleEmail $email): void {
            $email->people?->syncEmailColumns();
        });
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->type instanceof ContactEmailType ? $this->type->label() : (string) $this->type);
    }

    private function alreadyHasPrimary(): bool
    {
        if ($this->people_id === null) {
            return false;
        }

        return $this->newQuery()
            ->where('people_id', $this->people_id)
            ->where('is_primary', true)
            ->exists();
    }
}
