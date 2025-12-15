<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FeatureFlagSegment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class FeatureFlagSegmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FeatureFlagSegment');
    }

    public function view(AuthUser $authUser, FeatureFlagSegment $featureFlagSegment): bool
    {
        return $authUser->can('View:FeatureFlagSegment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FeatureFlagSegment');
    }

    public function update(AuthUser $authUser, FeatureFlagSegment $featureFlagSegment): bool
    {
        return $authUser->can('Update:FeatureFlagSegment');
    }

    public function delete(AuthUser $authUser, FeatureFlagSegment $featureFlagSegment): bool
    {
        return $authUser->can('Delete:FeatureFlagSegment');
    }

    public function restore(AuthUser $authUser, FeatureFlagSegment $featureFlagSegment): bool
    {
        return $authUser->can('Restore:FeatureFlagSegment');
    }

    public function forceDelete(AuthUser $authUser, FeatureFlagSegment $featureFlagSegment): bool
    {
        return $authUser->can('ForceDelete:FeatureFlagSegment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FeatureFlagSegment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FeatureFlagSegment');
    }

    public function replicate(AuthUser $authUser, FeatureFlagSegment $featureFlagSegment): bool
    {
        return $authUser->can('Replicate:FeatureFlagSegment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FeatureFlagSegment');
    }
}
