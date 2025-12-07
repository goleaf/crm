<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PeopleRepositoryInterface;
use App\Models\People;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class EloquentPeopleRepository implements PeopleRepositoryInterface
{
    public function find(int $id): ?People
    {
        return People::query()->with(['company', 'tags'])->find($id);
    }

    /**
     * @return LengthAwarePaginator<People>
     */
    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return People::query()
            ->with('company')
            ->when($term !== '', function ($query) use ($term): void {
                $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('primary_email', 'like', "%{$term}%")
                    ->orWhere('phone_mobile', 'like', "%{$term}%");
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): People
    {
        return People::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(People $people, array $data): People
    {
        $people->fill($data);
        $people->save();

        return $people;
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * @return Collection<int, People>
     */
    public function byCompany(int $companyId): Collection
    {
        return People::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }
}
