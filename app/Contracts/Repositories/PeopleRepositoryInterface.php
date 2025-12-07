<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\People;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PeopleRepositoryInterface extends RepositoryInterface
{
    public function find(int $id): ?People;

    /**
     * @return LengthAwarePaginator<People>
     */
    public function search(string $term, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): People;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(People $people, array $data): People;

    /**
     * @return Collection<int, People>
     */
    public function byCompany(int $companyId): Collection;
}
