<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Model;
use Illuminate\Support\Collection;

trait HasCrmTeams
{
    /**
     * @return Collection<int, Model>
     */
    public function crmTeams(): Collection
    {
        if (method_exists($this, 'allTeams')) {
            return $this->allTeams();
        }

        if (method_exists($this, 'teams')) {
            return $this->teams()->get();
        }

        return collect();
    }

    public function switchCrmTeam(Model $team): void
    {
        if (method_exists($this, 'switchTeam')) {
            $this->switchTeam($team);
        }
    }

    public function defaultCrmTeam(): ?Model
    {
        return method_exists($this, 'currentTeam') ? $this->currentTeam : null;
    }
}
