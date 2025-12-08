<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class EloquentCompanyRepository implements CompanyRepositoryInterface
{
    public function find(int $id): ?Company
    {
        return Company::query()->with(['childCompanies', 'people'])->find($id);
    }

    /**
     * @return LengthAwarePaginator<Company>
     */
    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return Company::query()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('primary_email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company
    {
        $company->fill($data);
        $company->save();

        return $company;
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * @return Collection<int, Company>
     */
    public function childrenOf(Company $company): Collection
    {
        return $company->childCompanies()->orderBy('name')->get();
    }
}
