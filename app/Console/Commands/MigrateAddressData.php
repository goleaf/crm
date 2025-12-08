<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\People;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

class MigrateAddressData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'world:migrate-legacy-addresses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy string addresses to World package foreign keys';

    private Collection $countries;
    private array $statesCache = [];
    private array $citiesCache = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting address migration...');

        // Load all countries once
        $this->info('Loading countries...');
        $this->countries = Country::all();

        $this->migrateCompanies();
        $this->migratePeople();

        $this->info('Migration completed.');
    }

    private function migrateCompanies(): void
    {
        $count = Company::count();
        $this->info("Migrating {$count} companies...");

        $bar = $this->output->createProgressBar($count);

        Company::chunk(100, function ($companies) use ($bar) {
            foreach ($companies as $company) {
                // Billing
                if ($company->billing_country) {
                    $countryId = $this->resolveCountry($company->billing_country);
                    if ($countryId) {
                        $company->billing_country_id = $countryId;

                        if ($company->billing_state) {
                            $stateId = $this->resolveState($countryId, $company->billing_state);
                            if ($stateId) {
                                $company->billing_state_id = $stateId;

                                if ($company->billing_city) {
                                    $cityId = $this->resolveCity($stateId, $company->billing_city);
                                    if ($cityId) {
                                        $company->billing_city_id = $cityId;
                                    }
                                }
                            }
                        }
                    }
                }

                // Shipping
                if ($company->shipping_country) {
                    $countryId = $this->resolveCountry($company->shipping_country);
                    if ($countryId) {
                        $company->shipping_country_id = $countryId;

                        if ($company->shipping_state) {
                            $stateId = $this->resolveState($countryId, $company->shipping_state);
                            if ($stateId) {
                                $company->shipping_state_id = $stateId;

                                if ($company->shipping_city) {
                                    $cityId = $this->resolveCity($stateId, $company->shipping_city);
                                    if ($cityId) {
                                        $company->shipping_city_id = $cityId;
                                    }
                                }
                            }
                        }
                    }
                }

                $company->saveQuietly();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function migratePeople(): void
    {
        $count = People::count();
        $this->info("Migrating {$count} people...");

        $bar = $this->output->createProgressBar($count);

        People::chunk(100, function ($people) use ($bar) {
            foreach ($people as $person) {
                if ($person->address_country) {
                    $countryId = $this->resolveCountry($person->address_country);
                    if ($countryId) {
                        $person->address_country_id = $countryId;

                        if ($person->address_state) {
                            $stateId = $this->resolveState($countryId, $person->address_state);
                            if ($stateId) {
                                $person->address_state_id = $stateId;

                                if ($person->address_city) {
                                    $cityId = $this->resolveCity($stateId, $person->address_city);
                                    if ($cityId) {
                                        $person->address_city_id = $cityId;
                                    }
                                }
                            }
                        }
                    }
                }

                $person->saveQuietly();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function resolveCountry(string $search): ?int
    {
        $search = trim($search);
        if (empty($search))
            return null;

        // Try exact match, ISO2, ISO3
        $country = $this->countries->first(function ($c) use ($search) {
            return strcasecmp($c->name, $search) === 0
                || strcasecmp($c->iso2, $search) === 0
                || strcasecmp($c->iso3, $search) === 0;
        });

        return $country?->id;
    }

    private function resolveState(int $countryId, string $search): ?int
    {
        $search = trim($search);
        if (empty($search))
            return null;

        if (!isset($this->statesCache[$countryId])) {
            $this->statesCache[$countryId] = State::where('country_id', $countryId)->get();
        }

        $state = $this->statesCache[$countryId]->first(function ($s) use ($search) {
            return strcasecmp($s->name, $search) === 0
                || (isset($s->code) && strcasecmp($s->code, $search) === 0); // Assuming code exists
        });

        return $state?->id;
    }

    private function resolveCity(int $stateId, string $search): ?int
    {
        $search = trim($search);
        if (empty($search))
            return null;

        // Cities can be many, cache carefully. 
        // We only cache per state which is reasonable.
        if (!isset($this->citiesCache[$stateId])) {
            $this->citiesCache[$stateId] = City::where('state_id', $stateId)->get();
        }

        $city = $this->citiesCache[$stateId]->first(function ($c) use ($search) {
            return strcasecmp($c->name, $search) === 0;
        });

        return $city?->id;
    }
}
