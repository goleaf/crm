<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Team;
use App\Models\User;
use App\Support\PersonNameFormatter;
use Filament\Auth\Events\Registered;

final readonly class CreatePersonalTeamListener
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $firstName = PersonNameFormatter::first($user->name, (string) $user->name);

        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->getAuthIdentifier(),
            'name' => "{$firstName}'s Team",
            'personal_team' => true,
        ]));
    }
}
