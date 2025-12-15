<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OCRDocument;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class OCRDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OCRDocument');
    }

    public function view(AuthUser $authUser, OCRDocument $oCRDocument): bool
    {
        return $authUser->can('View:OCRDocument');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OCRDocument');
    }

    public function update(AuthUser $authUser, OCRDocument $oCRDocument): bool
    {
        return $authUser->can('Update:OCRDocument');
    }

    public function delete(AuthUser $authUser, OCRDocument $oCRDocument): bool
    {
        return $authUser->can('Delete:OCRDocument');
    }

    public function restore(AuthUser $authUser, OCRDocument $oCRDocument): bool
    {
        return $authUser->can('Restore:OCRDocument');
    }

    public function forceDelete(AuthUser $authUser, OCRDocument $oCRDocument): bool
    {
        return $authUser->can('ForceDelete:OCRDocument');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OCRDocument');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OCRDocument');
    }

    public function replicate(AuthUser $authUser, OCRDocument $oCRDocument): bool
    {
        return $authUser->can('Replicate:OCRDocument');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OCRDocument');
    }
}
