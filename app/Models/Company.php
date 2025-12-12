<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\AddressData;
use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\CreationSource;
use App\Enums\CustomFields\NoteField;
use App\Enums\CustomFields\OpportunityField;
use App\Enums\CustomFields\TaskField;
use App\Enums\Industry;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTags;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUnsplashAssets;
use App\Models\Concerns\LogsActivity;
use App\Observers\CompanyObserver;
use App\Services\AvatarService;
use App\Services\DuplicateDetectionService;
use App\Support\Addresses\AddressValidator;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\TenantContextService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string         $name
 * @property string         $address
 * @property string         $country
 * @property string         $phone
 * @property Carbon|null    $deleted_at
 * @property CreationSource $creation_source
 * @property-read string $created_by
 * @property array<int, array<string, mixed>>|null $addresses
 */
#[ObservedBy(CompanyObserver::class)]
final class Company extends Model implements HasCustomFields, HasMedia
{
    use HasCreator;

    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use HasNotesAndNotables;
    use HasTags;
    use HasTaxonomies;
    use HasTeam;
    use HasUnsplashAssets;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;
    use UsesCustomFields;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'account_owner_id',
        'parent_company_id',
        'account_type',
        'ownership',
        'phone',
        'primary_email',
        'website',
        'industry',
        'revenue',
        'employee_count',
        'currency_code',
        'description',
        'billing_street',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'billing_country_id',
        'billing_state_id',
        'billing_city_id',
        'shipping_street',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'shipping_country_id',
        'shipping_state_id',
        'shipping_city_id',
        'social_links',
        'creation_source',
        'addresses',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'currency_code' => 'USD',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'industry' => Industry::class,
            'creation_source' => CreationSource::class,
            'revenue' => 'decimal:2',
            'employee_count' => 'integer',
            'social_links' => 'array',
            'addresses' => 'array',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        self::saving(function (self $company): void {
            $company->normalizeAddresses();
        });
    }

    /**
     * Cache for resolved custom fields to avoid repeated queries.
     *
     * @var array<string, CustomField|null>
     */
    private static array $customFieldCache = [];

    protected function getLogoAttribute(): string
    {
        $logo = $this->getFirstMediaUrl('logo');

        return $logo === '' || $logo === '0' ? resolve(AvatarService::class)->generateAuto(name: $this->name) : $logo;
    }

    /**
     * Team member responsible for managing the company account
     *
     * @return BelongsTo<User, $this>
     */
    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_owner_id');
    }

    /**
     * Account team members with their assigned roles and permissions.
     *
     * @return HasMany<AccountTeamMember, $this>
     */
    public function accountTeamMembers(): HasMany
    {
        return $this->hasMany(AccountTeamMember::class);
    }

    /**
     * Users collaborating on this account with pivot metadata.
     *
     * @return BelongsToMany<User, $this>
     */
    public function accountTeam(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_team_members')
            ->withPivot(['role', 'access_level'])
            ->withTimestamps();
    }

    /**
     * Ensure the account owner is always represented on the account team.
     */
    public function ensureAccountOwnerOnTeam(): void
    {
        if ($this->getKey() === null || $this->team_id === null || $this->account_owner_id === null) {
            return;
        }

        AccountTeamMember::query()->updateOrCreate(
            [
                'company_id' => $this->getKey(),
                'user_id' => $this->account_owner_id,
            ],
            [
                'team_id' => $this->team_id,
                'role' => AccountTeamRole::OWNER,
                'access_level' => AccountTeamAccessLevel::MANAGE,
            ],
        );
    }

    /**
     * Parent company in the hierarchy.
     *
     * @return BelongsTo<Company, $this>
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_company_id');
    }

    /**
     * Child companies in the hierarchy.
     *
     * @return HasMany<Company, $this>
     */
    public function childCompanies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_company_id');
    }

    /**
     * Determine if assigning the given parent would create a cycle in the hierarchy.
     */
    public function wouldCreateCycle(?int $parentId): bool
    {
        if ($parentId === null || $this->getKey() === null) {
            return false;
        }

        if ($parentId === $this->getKey()) {
            return true;
        }

        $visited = [];
        $currentId = $parentId;

        while ($currentId !== null) {
            if (in_array($currentId, $visited, true)) {
                break;
            }

            if ($currentId === $this->getKey()) {
                return true;
            }

            $visited[] = $currentId;
            $currentId = self::query()->whereKey($currentId)->value('parent_company_id');
        }

        return false;
    }

    /**
     * Filter companies within an inclusive employee count range.
     *
     * @param Builder<self> $query
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function employeeCountBetween(Builder $query, ?int $minEmployees = null, ?int $maxEmployees = null): Builder
    {
        return $query
            ->when($minEmployees !== null, fn (Builder $builder): Builder => $builder->where('employee_count', '>=', $minEmployees))
            ->when($maxEmployees !== null, fn (Builder $builder): Builder => $builder->where('employee_count', '<=', $maxEmployees));
    }

    /**
     * @return HasMany<People, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * @return HasMany<SupportCase, $this>
     */
    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class, 'company_id');
    }

    /**
     * @return HasMany<CompanyRevenue, $this>
     */
    public function annualRevenues(): HasMany
    {
        return $this->hasMany(CompanyRevenue::class);
    }

    /**
     * Latest annual revenue snapshot, preferring the most recent year.
     *
     * @return HasOne<CompanyRevenue, $this>
     */
    public function latestAnnualRevenue(): HasOne
    {
        return $this->hasOne(CompanyRevenue::class)
            ->ofMany([
                'year' => 'max',
                'created_at' => 'max',
            ], fn (Builder $query): Builder => $query->orderByDesc('year')->latest());
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * Attachments collection for company files.
     *
     * @return MorphMany<Media, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->media()
            ->where('collection_name', 'attachments')->latest();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();

        $this->addMediaCollection('attachments')
            ->useDisk(config('filesystems.default', 'public'));
    }

    /**
     * Find potential duplicate accounts.
     */
    public function findPotentialDuplicates(): Collection
    {
        return resolve(DuplicateDetectionService::class)->findDuplicates($this);
    }

    /**
     * Calculate how similar another company record is to this one.
     */
    public function calculateSimilarityScore(self $company): float
    {
        return resolve(DuplicateDetectionService::class)->calculateSimilarity(primary: $this, duplicate: $company);
    }

    /**
     * Total open opportunity value for this company.
     */
    public function getTotalPipelineValue(): float
    {
        return TenantContextService::withTenant($this->team_id, function (): float {
            /** @var CustomField|null $amountField */
            $amountField = $this->resolveCustomField(Opportunity::class, OpportunityField::AMOUNT->value);

            if ($amountField === null) {
                return 0.0;
            }

            /** @var CustomField|null $stageField */
            $stageField = $this->resolveCustomField(Opportunity::class, OpportunityField::STAGE->value);

            return $this->opportunities()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Opportunity $opportunity): ?float => $this->extractPipelineAmount($opportunity, $amountField, $stageField))
                ->filter(fn (?float $amount): bool => $amount !== null)
                ->sum();
        });
    }

    /**
     * Activity timeline for the company.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getActivityTimeline(int $limit = 25): Collection
    {
        $timeline = collect([
            [
                'type' => 'company',
                'id' => $this->getKey(),
                'title' => 'Company created',
                'summary' => $this->creator?->name !== null ? 'Created by ' . $this->creator->name : 'Created',
                'created_at' => $this->created_at,
            ],
        ]);

        if ($this->updated_at !== null && $this->updated_at->gt($this->created_at ?? $this->updated_at)) {
            $timeline = $timeline->push([
                'type' => 'company',
                'id' => $this->getKey(),
                'title' => 'Company updated',
                'summary' => 'Details updated',
                'created_at' => $this->updated_at,
            ]);
        }

        $noteField = $this->resolveCustomField(Note::class, NoteField::BODY->value);
        $taskDescriptionField = $this->resolveCustomField(Task::class, TaskField::DESCRIPTION->value);
        $taskStatusField = $this->resolveCustomField(Task::class, TaskField::STATUS->value);
        $taskPriorityField = $this->resolveCustomField(Task::class, TaskField::PRIORITY->value);
        $opportunityStageField = $this->resolveCustomField(Opportunity::class, OpportunityField::STAGE->value);
        $opportunityAmountField = $this->resolveCustomField(Opportunity::class, OpportunityField::AMOUNT->value);

        $timeline = $timeline->merge(
            $this->notes()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Note $note): array => [
                    'type' => 'note',
                    'id' => $note->getKey(),
                    'title' => $note->title,
                    'summary' => $this->buildSummary([
                        $this->extractCustomFieldValue($noteField, $note),
                    ]),
                    'created_at' => $note->created_at,
                ]),
        );

        $timeline = $timeline->merge(
            $this->tasks()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Task $task): array => [
                    'type' => 'task',
                    'id' => $task->getKey(),
                    'title' => $task->title,
                    'summary' => $this->formatTaskSummary($task, $taskStatusField, $taskPriorityField, $taskDescriptionField),
                    'created_at' => $task->created_at,
                ]),
        );

        $timeline = $timeline->merge(
            $this->opportunities()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Opportunity $opportunity): array => [
                    'type' => 'opportunity',
                    'id' => $opportunity->getKey(),
                    'title' => $opportunity->name,
                    'summary' => $this->formatOpportunitySummary($opportunity, $opportunityStageField, $opportunityAmountField),
                    'created_at' => $opportunity->created_at,
                ]),
        );

        return $timeline
            ->sortByDesc('created_at')
            ->values()
            ->take($limit);
    }

    private function extractPipelineAmount(Opportunity $opportunity, CustomField $amountField, ?CustomField $stageField): ?float
    {
        if ($stageField instanceof \Relaticle\CustomFields\Models\CustomField && ! $this->isOpportunityStageOpen($opportunity, $stageField)) {
            return null;
        }

        $value = $this->extractCustomFieldValue($amountField, $opportunity);

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function isOpportunityStageOpen(Opportunity $opportunity, CustomField $stageField): bool
    {
        $value = $this->extractCustomFieldValue($stageField, $opportunity);

        if ($value === null) {
            return true;
        }

        $label = $this->optionLabel($stageField, $value);

        return ! in_array($label, ['Closed Won', 'Closed Lost'], true);
    }

    private function formatTaskSummary(
        Task $task,
        ?CustomField $statusField,
        ?CustomField $priorityField,
        ?CustomField $descriptionField,
    ): string {
        $parts = [];

        if ($statusField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $parts[] = 'Status: ' . $this->optionLabel($statusField, $this->extractCustomFieldValue($statusField, $task));
        }

        if ($priorityField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $parts[] = 'Priority: ' . $this->optionLabel($priorityField, $this->extractCustomFieldValue($priorityField, $task));
        }

        if ($descriptionField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $parts[] = $this->formatRichText($this->extractCustomFieldValue($descriptionField, $task));
        }

        return $this->buildSummary($parts);
    }

    private function formatOpportunitySummary(Opportunity $opportunity, ?CustomField $stageField, ?CustomField $amountField): string
    {
        $parts = [];

        if ($stageField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $parts[] = 'Stage: ' . $this->optionLabel($stageField, $this->extractCustomFieldValue($stageField, $opportunity));
        }

        if ($amountField instanceof \Relaticle\CustomFields\Models\CustomField) {
            $amount = $this->extractCustomFieldValue($amountField, $opportunity);
            if (is_numeric($amount)) {
                $parts[] = 'Amount: $' . number_format((float) $amount, 2);
            }
        }

        return $this->buildSummary($parts);
    }

    private function extractCustomFieldValue(?CustomField $field, ?Model $model): mixed
    {
        if (! $field instanceof \Relaticle\CustomFields\Models\CustomField || ! $model instanceof \Illuminate\Database\Eloquent\Model) {
            return null;
        }

        $model->loadMissing('customFieldValues');

        return $model->getCustomFieldValue($field);
    }

    private function buildSummary(array $parts): string
    {
        $filtered = array_filter(
            array_map(
                static fn (mixed $value): string => trim((string) $value),
                $parts,
            ),
            static fn (string $value): bool => $value !== '',
        );

        return implode(' • ', $filtered);
    }

    private function formatRichText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return Str::of($value)
            ->stripTags()
            ->trim()
            ->limit(160)
            ->toString();
    }

    private function optionLabel(CustomField $field, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $field->loadMissing('options');

        if (is_numeric($value)) {
            $option = $field->options->firstWhere('id', (int) $value);
            if ($option !== null) {
                return (string) $option->name;
            }
        }

        return (string) $value;
    }

    private function resolveCustomField(string $entity, string $code): ?CustomField
    {
        $tenantId = TenantContextService::getCurrentTenantId() ?? 'global';
        $cacheKey = "{$tenantId}:{$entity}:{$code}";

        if (array_key_exists($cacheKey, self::$customFieldCache)) {
            $cached = self::$customFieldCache[$cacheKey];

            if (! $cached instanceof \Relaticle\CustomFields\Models\CustomField) {
                return null;
            }

            if ($cached->exists && CustomField::query()->whereKey($cached->getKey())->exists()) {
                return $cached;
            }

            unset(self::$customFieldCache[$cacheKey]);
        }

        self::$customFieldCache[$cacheKey] = CustomField::query()
            ->forEntity($entity)
            ->where('code', $code)
            ->first();

        return self::$customFieldCache[$cacheKey];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function billingCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'billing_country_id');
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function billingState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'billing_state_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function billingCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'billing_city_id');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function shippingCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'shipping_country_id');
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function shippingState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'shipping_state_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function shippingCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'shipping_city_id');
    }

    /**
     * @return Collection<int, AddressData>
     */
    public function addressCollection(): Collection
    {
        $addresses = $this->addresses ?? [];
        $addresses = is_array($addresses) ? $addresses : [];

        if ($addresses === []) {
            $addresses = $this->legacyAddressesFallback();
        }

        return collect($addresses)
            ->filter(static fn (mixed $address): bool => is_array($address))
            ->map(fn (array $address): AddressData => AddressData::fromArray($address))
            ->values();
    }

    public function addressFor(AddressType $type): ?AddressData
    {
        return $this->addressCollection()
            ->first(fn (AddressData $address): bool => $address->type === $type);
    }

    private function normalizeAddresses(): void
    {
        $validator = new AddressValidator;
        $rawAddresses = $this->addresses ?? [];

        if (! is_array($rawAddresses)) {
            $rawAddresses = [];
        }

        $addresses = $this->mergeLegacyAddresses($rawAddresses);

        $normalized = collect($addresses)
            ->filter(static fn (mixed $address): bool => is_array($address))
            ->map(fn (array $address): AddressData => $validator->validate($address))
            ->map(fn (AddressData $address): array => $address->toStorageArray())
            ->values()
            ->all();

        $this->setAttribute('addresses', $normalized);

        $this->syncLegacyAddressColumns($normalized);
    }

    /**
     * @param array<int, array<string, mixed>> $addresses
     */
    private function syncLegacyAddressColumns(array $addresses): void
    {
        $collection = collect($addresses)
            ->map(fn (array $address): AddressData => AddressData::fromArray($address));

        $billing = $collection->first(fn (AddressData $address): bool => $address->type === AddressType::BILLING);
        $shipping = $collection->first(fn (AddressData $address): bool => $address->type === AddressType::SHIPPING);

        $this->fillBillingFromAddress($billing);
        $this->fillShippingFromAddress($shipping);
    }

    private function fillBillingFromAddress(?AddressData $address): void
    {
        if (! $address instanceof \App\Data\AddressData) {
            return;
        }

        $this->billing_street = $address->line1;
        $this->billing_city = $address->city;
        $this->billing_state = $address->state;
        $this->billing_postal_code = $address->postal_code;
        $this->billing_country = $address->country_code;
    }

    private function fillShippingFromAddress(?AddressData $address): void
    {
        if (! $address instanceof \App\Data\AddressData) {
            return;
        }

        $this->shipping_street = $address->line1;
        $this->shipping_city = $address->city;
        $this->shipping_state = $address->state;
        $this->shipping_postal_code = $address->postal_code;
        $this->shipping_country = $address->country_code;
    }

    /**
     * @param array<int, array<string, mixed>> $addresses
     *
     * @return array<int, array<string, mixed>>
     */
    private function mergeLegacyAddresses(array $addresses): array
    {
        $collection = collect($addresses);

        $hasBilling = $collection->contains(fn (array $address): bool => Arr::get($address, 'type') === AddressType::BILLING->value);
        $hasShipping = $collection->contains(fn (array $address): bool => Arr::get($address, 'type') === AddressType::SHIPPING->value);

        if (! $hasBilling && filled($this->billing_street)) {
            $collection->push([
                'type' => AddressType::BILLING->value,
                'line1' => $this->billing_street,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postal_code' => $this->billing_postal_code,
                'country_code' => $this->billing_country ?? config('address.default_country', 'US'),
            ]);
        }

        if (! $hasShipping && filled($this->shipping_street)) {
            $collection->push([
                'type' => AddressType::SHIPPING->value,
                'line1' => $this->shipping_street,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country_code' => $this->shipping_country ?? config('address.default_country', 'US'),
            ]);
        }

        return $collection
            ->filter(static fn (array $address): bool => isset($address['type']) && isset($address['line1']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function legacyAddressesFallback(): array
    {
        $addresses = [];

        if (filled($this->billing_street)) {
            $addresses[] = [
                'type' => AddressType::BILLING->value,
                'line1' => $this->billing_street,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postal_code' => $this->billing_postal_code,
                'country_code' => $this->billing_country ?? config('address.default_country', 'US'),
            ];
        }

        if (filled($this->shipping_street)) {
            $addresses[] = [
                'type' => AddressType::SHIPPING->value,
                'line1' => $this->shipping_street,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country_code' => $this->shipping_country ?? config('address.default_country', 'US'),
            ];
        }

        return $addresses;
    }
}
