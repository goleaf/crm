<?php

declare(strict_types=1);

namespace App\Permissions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

final class TeamResolver implements PermissionsTeamResolver
{
    private int|string|null $teamId = null;

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->teamId !== null) {
            return $this->teamId;
        }

        $user = $this->user();

        return $user?->currentTeam?->getKey();
    }

    /**
     * @param int|string|Model|null $id
     */
    public function setPermissionsTeamId($id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        $this->teamId = $id;
    }

    private function user(): ?Authenticatable
    {
        return Auth::user();
    }
}
