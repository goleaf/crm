<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\AddressData;
use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\Addresses\AddressValidator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property array<string, mixed>|null $billing_address
 * @property array<string, mixed>|null $shipping_address
 * @property array<string, mixed>|null $social_links
 * @property array<int, array<string, mixed>>|null $addresses
 */
final class Account extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    use HasTeam;
    use HasUniqueSlug;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'team_id',
        'parent_id',
        'type',
        'industry',
        'annual_revenue',
        'employee_count',
        'currency',
        'website',
        'social_links',
        'billing_address',
        'shipping_address',
        'custom_fields',
        'owner_id',
        'assigned_to_id',
        'addresses',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annual_revenue' => 'decimal:2',
            'type' => AccountType::class,
            'industry' => Industry::class,
            'employee_count' => 'integer',
            'social_links' => 'array',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'custom_fields' => 'array',
            'addresses' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (self $account): void {
            if ($account->team_id === null && auth()->check() && auth()->user()?->currentTeam !== null) {
                $account->team_id = auth()->user()->currentTeam->getKey();
            }
        });

        self::saving(static function (self $account): void {
            $account->normalizeAddresses();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Account, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Many-to-many relationship with contacts (people).
     *
     * @return BelongsToMany<People, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(People::class, 'account_people')
            ->withPivot(['is_primary', 'role'])
            ->withTimestamps();
    }

    /**
     * Opportunities associated with this account.
     *
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Support cases associated with this account.
     *
     * @return HasMany<SupportCase, $this>
     */
    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }

    /**
     * Notes associated with this account (many-to-many polymorphic relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<Note, $this>
     */
    public function notes(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Note::class, 'noteable', 'noteables');
    }

    /**
     * Tasks associated with this account (many-to-many polymorphic relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<Task, $this>
     */
    public function tasks(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * Determine if assigning the given parent would create a cycle.
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
            $currentId = self::query()
                ->whereKey($currentId)
                ->value('parent_id');
        }

        return false;
    }

    /**
     * Attachments collection for account files.
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
        $this->addMediaCollection('attachments')
            ->useDisk(config('filesystems.default', 'public'));
    }

    /**
     * Consolidated activity timeline combining notes, tasks, opportunities, cases, and attachments.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getActivityTimeline(int $limit = 25): Collection
    {
        $timeline = collect([
            [
                'type' => 'account',
                'id' => $this->getKey(),
                'title' => 'Account created',
                'summary' => 'Record created',
                'created_at' => $this->created_at,
            ],
        ]);

        if ($this->updated_at !== null && $this->updated_at->gt($this->created_at ?? $this->updated_at)) {
            $timeline = $timeline->push([
                'type' => 'account',
                'id' => $this->getKey(),
                'title' => 'Account updated',
                'summary' => 'Details updated',
                'created_at' => $this->updated_at,
            ]);
        }

        // Add notes
        $timeline = $timeline->merge(
            $this->notes()
                ->get()
                ->map(fn (Note $note): array => [
                    'type' => 'note',
                    'id' => $note->getKey(),
                    'title' => $note->title ?? 'Note',
                    'summary' => method_exists($note, 'plainBody') ? $note->plainBody() : null,
                    'created_at' => $note->created_at,
                ])
        );

        // Add tasks
        $timeline = $timeline->merge(
            $this->tasks()
                ->get()
                ->map(fn (Task $task): array => [
                    'type' => 'task',
                    'id' => $task->getKey(),
                    'title' => $task->title ?? 'Task',
                    'summary' => $this->formatTaskSummary($task),
                    'created_at' => $task->created_at,
                ])
        );

        // Add opportunities
        $timeline = $timeline->merge(
            $this->opportunities()
                ->get()
                ->map(fn (Opportunity $opportunity): array => [
                    'type' => 'opportunity',
                    'id' => $opportunity->getKey(),
                    'title' => $opportunity->name ?? 'Opportunity #'.$opportunity->getKey(),
                    'summary' => $this->formatOpportunitySummary($opportunity),
                    'created_at' => $opportunity->created_at,
                ])
        );

        // Add cases
        $timeline = $timeline->merge(
            $this->cases()
                ->get()
                ->map(fn (SupportCase $case): array => [
                    'type' => 'case',
                    'id' => $case->getKey(),
                    'title' => $case->subject ?? 'Case #'.$case->case_number,
                    'summary' => $this->formatCaseSummary($case),
                    'created_at' => $case->created_at,
                ])
        );

        // Add attachments
        $timeline = $timeline->merge(
            $this->attachments
                ->map(fn (Media $media): array => [
                    'type' => 'attachment',
                    'id' => $media->getKey(),
                    'title' => $media->file_name,
                    'summary' => $media->mime_type,
                    'created_at' => $media->created_at,
                ])
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

    private function formatOpportunitySummary(Opportunity $opportunity): string
    {
        $parts = [];

        if (isset($opportunity->stage)) {
            $parts[] = 'Stage: '.$opportunity->stage;
        }

        if (isset($opportunity->amount)) {
            $parts[] = 'Amount: $'.number_format((float) $opportunity->amount, 2);
        }

        return $parts === [] ? 'Opportunity activity' : implode(' • ', $parts);
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
     * @param  array<int, array<string, mixed>>  $addresses
     */
    private function syncLegacyAddressColumns(array $addresses): void
    {
        $collection = collect($addresses)
            ->map(fn (array $address): AddressData => AddressData::fromArray($address));

        $billing = $collection->first(fn (AddressData $address): bool => $address->type === AddressType::BILLING);
        $shipping = $collection->first(fn (AddressData $address): bool => $address->type === AddressType::SHIPPING);

        $this->billing_address = $billing?->toLegacyArray();
        $this->shipping_address = $shipping?->toLegacyArray();
    }

    /**
     * @param  array<int, array<string, mixed>>  $addresses
     * @return array<int, array<string, mixed>>
     */
    private function mergeLegacyAddresses(array $addresses): array
    {
        $collection = collect($addresses);

        $hasBilling = $collection->contains(fn (array $address): bool => Arr::get($address, 'type') === AddressType::BILLING->value);
        $hasShipping = $collection->contains(fn (array $address): bool => Arr::get($address, 'type') === AddressType::SHIPPING->value);

        if (! $hasBilling && $this->billing_address !== null && filled($this->billing_address['street'] ?? null)) {
            $collection->push([
                'type' => AddressType::BILLING->value,
                'line1' => $this->billing_address['street'] ?? '',
                'line2' => $this->billing_address['street2'] ?? null,
                'city' => $this->billing_address['city'] ?? null,
                'state' => $this->billing_address['state'] ?? null,
                'postal_code' => $this->billing_address['postal_code'] ?? null,
                'country_code' => $this->billing_address['country'] ?? config('address.default_country', 'US'),
            ]);
        }

        if (! $hasShipping && $this->shipping_address !== null && filled($this->shipping_address['street'] ?? null)) {
            $collection->push([
                'type' => AddressType::SHIPPING->value,
                'line1' => $this->shipping_address['street'] ?? '',
                'line2' => $this->shipping_address['street2'] ?? null,
                'city' => $this->shipping_address['city'] ?? null,
                'state' => $this->shipping_address['state'] ?? null,
                'postal_code' => $this->shipping_address['postal_code'] ?? null,
                'country_code' => $this->shipping_address['country'] ?? config('address.default_country', 'US'),
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

        if ($this->billing_address !== null && filled($this->billing_address['street'] ?? null)) {
            $addresses[] = [
                'type' => AddressType::BILLING->value,
                'line1' => $this->billing_address['street'] ?? '',
                'line2' => $this->billing_address['street2'] ?? null,
                'city' => $this->billing_address['city'] ?? null,
                'state' => $this->billing_address['state'] ?? null,
                'postal_code' => $this->billing_address['postal_code'] ?? null,
                'country_code' => $this->billing_address['country'] ?? config('address.default_country', 'US'),
            ];
        }

        if ($this->shipping_address !== null && filled($this->shipping_address['street'] ?? null)) {
            $addresses[] = [
                'type' => AddressType::SHIPPING->value,
                'line1' => $this->shipping_address['street'] ?? '',
                'line2' => $this->shipping_address['street2'] ?? null,
                'city' => $this->shipping_address['city'] ?? null,
                'state' => $this->shipping_address['state'] ?? null,
                'postal_code' => $this->shipping_address['postal_code'] ?? null,
                'country_code' => $this->shipping_address['country'] ?? config('address.default_country', 'US'),
            ];
        }

        return $addresses;
    }
}
