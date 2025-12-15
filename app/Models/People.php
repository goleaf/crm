<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactEmailType;
use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTags;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\LogsActivity;
use App\Observers\PeopleObserver;
use App\Services\AvatarService;
use App\Services\Opportunities\OpportunityMetricsService;
use App\Support\PersonNameFormatter;
use Database\Factories\PeopleFactory;
use HosmelQ\NameOfPerson\PersonName;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

/**
 * @property Carbon|null    $deleted_at
 * @property CreationSource $creation_source
 */
#[ObservedBy(PeopleObserver::class)]
final class People extends Model implements HasCustomFields
{
    use HasCreator;

    /** @use HasFactory<PeopleFactory> */
    use HasFactory;

    use HasNotesAndNotables;
    use HasTags;
    use HasTaxonomies;
    use HasTeam;
    use LogsActivity;
    use SoftDeletes;
    use UsesCustomFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company_id',
        'primary_company_id',
        'persona_id',
        'primary_email',
        'alternate_email',
        'phone_mobile',
        'phone_office',
        'phone_home',
        'phone_fax',
        'job_title',
        'department',
        'role',
        'reports_to_id',
        'birthdate',
        'assistant_name',
        'assistant_phone',
        'assistant_email',
        'address_street',
        'address_city',
        'address_city_id',
        'address_state',
        'address_state_id',
        'address_postal_code',
        'address_country',
        'address_country_id',
        'social_links',
        'lead_source',
        'is_portal_user',
        'portal_username',
        'portal_last_login_at',
        'sync_enabled',
        'sync_reference',
        'synced_at',
        'segments',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'is_portal_user' => false,
        'sync_enabled' => false,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'birthdate' => 'date',
            'portal_last_login_at' => 'datetime',
            'synced_at' => 'datetime',
            'is_portal_user' => 'boolean',
            'sync_enabled' => 'boolean',
            'social_links' => 'array',
            'segments' => 'array',
        ];
    }

    protected function getPersonNameAttribute(): ?PersonName
    {
        return PersonNameFormatter::make($this->name);
    }

    protected function getNameInitialsAttribute(): string
    {
        return PersonNameFormatter::initials($this->name, 2, '');
    }

    /**
     * @return HasMany<PeopleEmail, $this>
     */
    public function emails(): HasMany
    {
        return $this->hasMany(PeopleEmail::class, 'people_id');
    }

    protected function getPrimaryEmailAttribute(?string $value): ?string
    {
        if ($this->relationLoaded('emails')) {
            $primary = $this->emails->firstWhere('is_primary', true) ?? $this->emails->first();

            return $primary?->email ?? $value;
        }

        return $value;
    }

    public function syncEmailColumns(): void
    {
        $this->loadMissing('emails');

        $primary = $this->emails->firstWhere('is_primary', true) ?? $this->emails->first();

        if ($primary !== null && $primary->is_primary !== true) {
            $primary->forceFill(['is_primary' => true])->saveQuietly();
        }

        $secondary = $this->emails
            ->reject(fn (PeopleEmail $email): bool => $primary !== null && $email->is($primary))
            ->first();

        $needsUpdate = $this->primary_email !== ($primary->email ?? null)
            || $this->alternate_email !== ($secondary->email ?? null);

        if ($needsUpdate) {
            $this->forceFill([
                'primary_email' => $primary?->email,
                'alternate_email' => $secondary?->email,
            ])->saveQuietly();
        }
    }

    public function ensureEmailsFromColumns(): void
    {
        $existing = $this->emails()->pluck('email')->all();

        $payloads = [];

        if ($this->primary_email !== null && ! in_array($this->primary_email, $existing, true)) {
            $payloads[] = [
                'email' => $this->primary_email,
                'type' => ContactEmailType::Work,
                'is_primary' => true,
            ];
        }

        if ($this->alternate_email !== null && ! in_array($this->alternate_email, $existing, true)) {
            $payloads[] = [
                'email' => $this->alternate_email,
                'type' => ContactEmailType::Personal,
                'is_primary' => false,
            ];
        }

        foreach ($payloads as $payload) {
            $this->emails()->create($payload);
        }

        if ($payloads !== []) {
            $this->load('emails');
        }
    }

    protected function getAvatarAttribute(): string
    {
        return resolve(AvatarService::class)->generateAuto(name: $this->name, initialCount: 1);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function primaryCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'primary_company_id');
    }

    /**
     * @return BelongsTo<ContactPersona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(ContactPersona::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function addressCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'address_country_id');
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function addressState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'address_state_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function addressCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'address_city_id');
    }

    /**
     * @return BelongsToMany<ContactRole, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(ContactRole::class, 'contact_role_people');
    }

    /**
     * @return HasOne<CommunicationPreference, $this>
     */
    public function communicationPreferences(): HasOne
    {
        return $this->hasOne(CommunicationPreference::class, 'people_id');
    }

    /**
     * @return HasOne<PortalUser, $this>
     */
    public function portalUser(): HasOne
    {
        return $this->hasOne(PortalUser::class, 'people_id');
    }

    /**
     * Many-to-many relationship with accounts.
     *
     * @return BelongsToMany<Account, $this>
     */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_people')
            ->withPivot(['is_primary', 'role'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Group, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to_id');
    }

    /**
     * @return HasMany<People, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to_id');
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'contact_id');
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * @return HasMany<SupportCase, $this>
     */
    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class, 'contact_id');
    }

    /**
     * Activity timeline blending notes, tasks, cases, and opportunities.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getActivityTimeline(int $limit = 25): Collection
    {
        $timeline = collect([
            [
                'type' => 'contact',
                'id' => $this->getKey(),
                'title' => 'Contact created',
                'summary' => $this->creator?->name !== null ? 'Created by ' . $this->creator->name : 'Created',
                'created_at' => $this->created_at,
            ],
        ]);

        if ($this->updated_at !== null && $this->updated_at->gt($this->created_at ?? $this->updated_at)) {
            $timeline = $timeline->push([
                'type' => 'contact',
                'id' => $this->getKey(),
                'title' => 'Contact updated',
                'summary' => 'Profile updated',
                'created_at' => $this->updated_at,
            ]);
        }

        $timeline = $timeline->merge(
            $this->notes()
                ->get()
                ->map(fn (Note $note): array => [
                    'type' => 'note',
                    'id' => $note->getKey(),
                    'title' => $note->title,
                    'summary' => method_exists($note, 'plainBody') ? $note->plainBody() : null,
                    'created_at' => $note->created_at,
                ]),
        );

        $timeline = $timeline->merge(
            $this->tasks()
                ->get()
                ->map(fn (Task $task): array => [
                    'type' => 'task',
                    'id' => $task->getKey(),
                    'title' => $task->title,
                    'summary' => $this->formatTaskSummary($task),
                    'created_at' => $task->created_at,
                ]),
        );

        $timeline = $timeline->merge(
            $this->cases()
                ->get()
                ->map(fn (SupportCase $case): array => [
                    'type' => 'case',
                    'id' => $case->getKey(),
                    'title' => $case->subject,
                    'summary' => $this->formatCaseSummary($case),
                    'created_at' => $case->created_at,
                ]),
        );

        $metrics = resolve(OpportunityMetricsService::class);

        $timeline = $timeline->merge(
            $this->opportunities()
                ->with('customFieldValues.customField')
                ->get()
                ->map(fn (Opportunity $opportunity): array => [
                    'type' => 'opportunity',
                    'id' => $opportunity->getKey(),
                    'title' => $opportunity->name ?? 'Opportunity #' . $opportunity->getKey(),
                    'summary' => $this->formatOpportunitySummary($opportunity, $metrics),
                    'created_at' => $opportunity->created_at,
                ]),
        );

        return $timeline
            ->filter(fn (array $entry): bool => $entry['created_at'] !== null)
            ->sortByDesc('created_at')
            ->values()
            ->take($limit);
    }

    private function formatTaskSummary(Task $task): string
    {
        $parts = array_filter([
            $task->status ?? null,
            $task->priority ?? null,
        ], fn (?string $part): bool => $part !== null && trim($part) !== '');

        return $parts === [] ? 'Task logged' : implode(' • ', array_map(ucfirst(...), $parts));
    }

    private function formatCaseSummary(SupportCase $case): string
    {
        $status = $case->status?->getLabel() ?? null;
        $priority = $case->priority?->getLabel() ?? null;

        return collect([$status, $priority])
            ->filter(fn (?string $value): bool => $value !== null && trim($value) !== '')
            ->implode(' • ');
    }

    private function formatOpportunitySummary(Opportunity $opportunity, OpportunityMetricsService $metrics): string
    {
        $stage = $metrics->stageLabel($opportunity);
        $amount = $metrics->amount($opportunity);

        $parts = [];

        if ($stage !== null) {
            $parts[] = 'Stage: ' . $stage;
        }

        if (is_numeric($amount)) {
            $parts[] = 'Amount: $' . number_format($amount, 2);
        }

        return $parts === [] ? 'Opportunity activity' : implode(' • ', $parts);
    }

    public function assignRole(string $roleName): void
    {
        $role = ContactRole::query()->firstOrCreate(
            [
                'team_id' => $this->team_id,
                'name' => $roleName,
            ],
            [
                'description' => null,
            ],
        );

        $this->roles()->syncWithoutDetaching([$role->getKey()]);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function canReceiveCommunication(string $channel): bool
    {
        $preferences = $this->communicationPreferences;

        if ($preferences === null) {
            return true;
        }

        return $preferences->canContact($channel);
    }

    public function grantPortalAccess(): PortalUser
    {
        return resolve(\App\Services\Contacts\PortalAccessService::class)->grantAccess($this);
    }

    public function revokePortalAccess(): void
    {
        $this->portalUser()->delete();
    }

    public function getSimilarityScore(People $other): float
    {
        return resolve(\App\Services\Contacts\ContactDuplicateDetectionService::class)->calculateSimilarity($this, $other);
    }
}
