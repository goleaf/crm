<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AvatarService;
use Database\Factories\TeamFactory;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

/**
 * Team Model - Multi-tenant organization entity
 * 
 * Represents a team/organization in the multi-tenant CRM system. Teams serve as the primary
 * tenant boundary, containing all related CRM entities (people, companies, tasks, etc.).
 * Extends Laravel Jetstream's Team model with CRM-specific relationships and avatar functionality.
 * 
 * @property int $id Primary key
 * @property int $user_id Team owner user ID
 * @property string $name Team name
 * @property bool $personal_team Whether this is a personal team
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\People> $people
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Company> $companies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lead> $leads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Opportunity> $opportunities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Note> $notes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SupportCase> $supportCases
 * 
 * @method static \Database\Factories\TeamFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Team query()
 */
final class Team extends JetstreamTeam implements HasAvatar
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    /**
     * Check if this is a personal team
     * 
     * Personal teams are automatically created for individual users and typically
     * contain only that user's personal data.
     * 
     * @return bool True if this is a personal team, false otherwise
     */
    public function isPersonalTeam(): bool
    {
        return $this->personal_team;
    }

    /**
     * Generate avatar URL for Filament interface
     * 
     * Creates a generated avatar using the team name with consistent styling.
     * Used in Filament's tenant switcher and team displays.
     * 
     * @return string Generated avatar URL
     */
    public function getFilamentAvatarUrl(): string
    {
        return resolve(AvatarService::class)->generate(name: $this->name, bgColor: '#000000', textColor: '#ffffff');
    }

    /**
     * @return HasMany<People, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(People::class);
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * @return HasMany<SupportCase, $this>
     */
    public function supportCases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }
}
