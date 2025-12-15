<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\CompanyRevenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CreationSource $creation_source
 */
final class CompanyRevenue extends Model
{
    use HasCreator;

    /** @use HasFactory<CompanyRevenueFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'year',
        'amount',
        'currency_code',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'currency_code' => 'USD',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount' => 'decimal:2',
            'creation_source' => CreationSource::class,
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function save(array $options = []): bool
    {
        $this->team_id ??= $this->resolveTeamId();
        $this->creator_id ??= auth('web')->id();
        $this->creation_source ??= CreationSource::WEB;
        $this->currency_code ??= $this->company?->currency_code ?? config('company.default_currency', 'USD');

        $saved = parent::save($options);

        if ($saved) {
            $this->syncCompanyRevenue();
        }

        return $saved;
    }

    public function delete(): ?bool
    {
        $deleted = parent::delete();

        if ($deleted) {
            $this->syncCompanyRevenue();
        }

        return $deleted;
    }

    private function resolveTeamId(): ?int
    {
        if ($this->relationLoaded('company') && $this->company !== null) {
            return $this->company->team_id;
        }

        if ($this->company_id !== null) {
            $teamId = Company::query()->whereKey($this->company_id)->value('team_id');

            if ($teamId !== null) {
                return (int) $teamId;
            }
        }

        if (auth('web')->check()) {
            return auth('web')->user()?->currentTeam?->getKey();
        }

        return null;
    }

    private function syncCompanyRevenue(): void
    {
        $company = $this->company ?? ($this->company_id !== null ? Company::query()->find($this->company_id) : null);

        if ($company === null) {
            return;
        }

        $latest = $company->annualRevenues()
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->first();

        $company->forceFill([
            'revenue' => $latest?->amount,
        ])->saveQuietly();
    }
}
