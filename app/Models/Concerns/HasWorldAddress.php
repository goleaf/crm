<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

/**
 * Trait for models with world address fields
 *
 * Provides relationships to country, state, and city from nnjeim/world package.
 *
 * Required columns:
 * - country_id (nullable)
 * - state_id (nullable)
 * - city_id (nullable)
 *
 * Optional columns:
 * - street_address (string)
 * - postal_code (string)
 */
trait HasWorldAddress
{
    /**
     * Get the country for this address
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Nnjeim\World\Models\Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the state for this address
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Nnjeim\World\Models\State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the city for this address
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Nnjeim\World\Models\City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get formatted address string
     */
    protected function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street_address ?? null,
            $this->city?->name,
            $this->state?->name,
            $this->postal_code ?? null,
            $this->country?->name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get short address (city, state, country)
     */
    protected function getShortAddressAttribute(): string
    {
        $parts = array_filter([
            $this->city?->name,
            $this->state?->name,
            $this->country?->name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Scope to filter by country
     */
    protected function scopeInCountry($query, int|string $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to filter by state
     */
    protected function scopeInState($query, int|string $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    /**
     * Scope to filter by city
     */
    protected function scopeInCity($query, int|string $cityId)
    {
        return $query->where('city_id', $cityId);
    }
}
