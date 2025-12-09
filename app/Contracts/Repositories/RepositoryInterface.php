<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal repository contract for CRUD operations.
 *
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /**
     * @return TModel|null
     */
    public function find(int $id): ?Model;

    /**
     * @param array<string, mixed> $data
     *
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * @param array<string, mixed> $data
     *
     * @return TModel
     */
    public function update(Model $model, array $data): Model;

    public function delete(Model $model): bool;
}
