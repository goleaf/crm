<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Models\People;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PortalAccessService
{
    public function grantAccess(People $contact): PortalUser
    {
        $password = $this->generateSecurePassword();

        // Here you could dispatch a notification job with $password.
        return PortalUser::updateOrCreate(
            ['people_id' => $contact->getKey()],
            [
                'email' => $contact->primary_email ?? $contact->alternate_email ?? $contact->portal_username ?? Str::uuid().'@example.com',
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );
    }

    public function revokeAccess(People $contact): void
    {
        $contact->portalUser()->delete();
    }

    public function resetPassword(PortalUser $portalUser): string
    {
        $password = $this->generateSecurePassword();
        $portalUser->forceFill([
            'password' => Hash::make($password),
        ])->save();

        return $password;
    }

    private function generateSecurePassword(): string
    {
        return Str::password(16);
    }
}
