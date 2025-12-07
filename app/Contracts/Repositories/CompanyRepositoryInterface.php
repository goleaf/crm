<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CompanyRepositoryInterface extends RepositoryInterface
{
    public function find(int $id): ?Company;

    /**
     * @return LengthAwarePaginator<Company>
     */
    public function search(string $term, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company;

    /**
     * @return Collection<int, Company>
     */
    public function childrenOf(Company $company): Collection;
}
