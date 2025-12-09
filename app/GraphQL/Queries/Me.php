<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class Me
{
    /**
     * Return the currently authenticated user.
     */
    public function __invoke(): User
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user();

        return $user;
    }
}
