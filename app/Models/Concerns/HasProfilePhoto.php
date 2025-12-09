<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\AvatarService;
use App\Support\PersonNameFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

trait HasProfilePhoto
{
    /**
     * Update the user's profile photo.
     */
    public function updateProfilePhoto(string $photo): void
    {
        tap($this->profile_photo_path, function ($previous) use ($photo): void {
            $this->forceFill(['profile_photo_path' => $photo])->save();

            if ($previous) {
                Storage::disk($this->profilePhotoDisk())->delete($previous);
            }
        });
    }

    /**
     * Delete the user's profile photo.
     */
    public function deleteProfilePhoto(): void
    {
        if (is_null($this->profile_photo_path)) {
            return;
        }

        Storage::disk($this->profilePhotoDisk())->delete($this->profile_photo_path);

        $this->forceFill([
            'profile_photo_path' => null,
        ])->save();
    }

    /**
     * Get the URL to the user's profile photo.
     *
     * @return Attribute<string, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->profile_photo_path
            ? Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path)
            : $this->defaultProfilePhotoUrl());
    }

    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     */
    protected function defaultProfilePhotoUrl(): string
    {
        $initials = PersonNameFormatter::initials($this->name, 2, '?');

        return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the disk that profile photos should be stored on.
     */
    protected function profilePhotoDisk(): string
    {
        return config('jetstream.profile_photo_disk', 'public');
    }

    protected function getAvatarAttribute(): string
    {
        return $this->getFilamentAvatarUrl();
    }

    public function getFilamentAvatarUrl(): string
    {
        return $this->profile_photo_path
            ? Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path)
            : resolve(AvatarService::class)->generate($this->name);
    }
}
