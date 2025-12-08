<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ExampleRepositoryInterface;
use App\Models\People;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of example repository.
 *
 * Repositories encapsulate data access logic.
 * Register interface binding in AppServiceProvider.
 */
final class EloquentExampleRepository implements ExampleRepositoryInterface
{
    public function findById(int $id): ?People
    {
        return People::find($id);
    }

    public function findByEmail(string $email): ?People
    {
        return People::where('primary_email', $email)->first();
    }

    public function search(string $query, int $teamId): Collection
    {
        return People::where('team_id', $teamId)
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('primary_email', 'like', "%{$query}%");
            })
            ->get();
    }

    public function create(array $data): People
    {
        return People::create($data);
    }

    public function update(People $contact, array $data): People
    {
        $contact->update($data);

        return $contact->fresh();
    }

    public function delete(People $contact): bool
    {
        return (bool) $contact->delete();
    }
}
