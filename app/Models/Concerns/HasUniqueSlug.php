<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Support\Str;
use WillVincent\LaravelUnique\HasUniqueNames;

trait HasUniqueSlug
{
    use HasUniqueNames {
        HasUniqueNames::bootHasUniqueNames as baseBootHasUniqueNames;
        HasUniqueNames::getConstraintValues as baseGetConstraintValues;
    }

    protected string $uniqueField = 'slug';

    protected string $uniqueBaseField = 'name';

    protected string $uniqueSuffixFormat = '-{n}';

    protected bool $reslugOnBaseChange = false;

    /**
     * @var list<string>
     */
    protected array $constraintFields = ['team_id'];

    protected static function bootHasUniqueNames(): void
    {
        static::saving(function (self $model): void {
            $model->syncUniqueBaseValue();
            $model->applyUniqueConstraintFallbacks();
        });

        static::baseBootHasUniqueNames();
    }

    protected function syncUniqueBaseValue(): void
    {
        $uniqueField = $this->uniqueField ?? 'slug';
        $baseField = $this->uniqueBaseField ?? 'name';

        if ($this->reslugOnBaseChange === true && $this->isDirty($baseField) && ! $this->isDirty($uniqueField)) {
            $this->setAttribute($uniqueField, (string) $this->getAttribute($baseField));
        }

        $rawValue = (string) $this->getAttribute($uniqueField);
        if ($rawValue === '') {
            $rawValue = (string) $this->getAttribute($baseField);
        }

        $this->setAttribute($uniqueField, $this->slugifyUniqueValue($rawValue));
    }

    protected function slugifyUniqueValue(string $value): string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            return Str::random(8);
        }

        return $slug;
    }

    protected function applyUniqueConstraintFallbacks(): void
    {
        if (in_array('team_id', $this->constraintFields, true) && $this->getAttribute('team_id') === null) {
            $teamId = CurrentTeamResolver::resolveId();

            if ($teamId !== null) {
                $this->setAttribute('team_id', $teamId);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConstraintValues(array $constraintFields): array
    {
        $values = $this->baseGetConstraintValues($constraintFields);

        if (in_array('team_id', $constraintFields, true) && ($values['team_id'] ?? null) === null) {
            $values['team_id'] = CurrentTeamResolver::resolveId();
        }

        return $values;
    }
}
