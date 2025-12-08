<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Helper methods for models that generate reference numbers with sequences.
 */
trait HasReferenceNumbering
{
    protected function primeReferenceCounter(string $dateColumn): void
    {
        if (! method_exists($this, 'getReferenceSequentialConfig')) {
            return;
        }

        $config = $this->getReferenceSequentialConfig();
        $table = $config['counter_table'] ?? config('referenceable.sequential.counter_table', 'model_reference_counters');
        $resetFrequency = $config['reset_frequency'] ?? 'never';
        $resetKey = $this->buildReferenceResetKey($resetFrequency);
        $counterKey = static::class.($resetKey !== null ? ':'.$resetKey : '');

        if (DB::table($table)->where('key', $counterKey)->exists()) {
            return;
        }

        $now = \Illuminate\Support\Facades\Date::now();
        $query = $this->newQuery();

        switch ($resetFrequency) {
            case 'daily':
                $query->whereDate($dateColumn, $now->toDateString());

                break;
            case 'monthly':
                $query->whereYear($dateColumn, $now->year)->whereMonth($dateColumn, $now->month);

                break;
            case 'yearly':
                $query->whereYear($dateColumn, $now->year);

                break;
        }

        $seed = (int) $query->max('sequence');
        $currentSequence = (int) ($this->sequence ?? $this->extractSequenceNumber($this->{$this->getReferenceColumn()} ?? null) ?? 0);
        $seed = max($seed, $currentSequence);
        $startValue = max((int) ($config['start'] ?? 1) - 1, 0);

        DB::table($table)->insert([
            'key' => $counterKey,
            'value' => max($seed, $startValue),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected function extractSequenceNumber(?string $reference): ?int
    {
        if (! is_string($reference)) {
            return null;
        }

        if (preg_match('/(\\d+)(?!.*\\d)/', $reference, $matches) !== 1) {
            return null;
        }

        $normalized = ltrim($matches[1], '0');

        return $normalized === '' ? (int) $matches[1] : (int) $normalized;
    }

    private function buildReferenceResetKey(string $resetFrequency): ?string
    {
        $now = \Illuminate\Support\Facades\Date::now();

        return match ($resetFrequency) {
            'daily' => $now->format('Y-m-d'),
            'monthly' => $now->format('Y-m'),
            'yearly' => $now->format('Y'),
            default => null,
        };
    }
}
