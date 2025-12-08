<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\People;
use Illuminate\Database\Eloquent\Collection;

/**
 * Example repository interface demonstrating repository pattern.
 *
 * Interfaces allow swapping implementations (e.g., Eloquent vs. API vs. Cache).
 */
interface ExampleRepositoryInterface
{
    public function findById(int $id): ?People;

    public function findByEmail(string $email): ?People;

    /**
     * @return Collection<int, People>
     */
    public function search(string $query, int $teamId): Collection;

    public function create(array $data): People;

    public function update(People $contact, array $data): People;

    public function delete(People $contact): bool;
}
