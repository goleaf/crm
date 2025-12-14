<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Admin\LoginHistory;
use App\Models\Admin\PasswordHistory;
use App\Models\Admin\PasswordPolicy;
use App\Models\Admin\UserActivity;
use App\Models\Admin\UserSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

final class AdminService
{
    /**
     * Create a new user with admin panel features
     */
    public function createUser(array $data): User
    {
        $passwordPolicy = PasswordPolicy::getDefault();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'user_type' => $data['user_type'] ?? 'regular',
            'status' => $data['status'] ?? 'active',
            'password_policy_id' => $passwordPolicy?->id,
            'password_expires_at' => $passwordPolicy?->max_age_days
                ? now()->addDays($passwordPolicy->max_age_days)
                : null,
            'force_password_change' => $data['force_password_change'] ?? false,
        ]);

        // Add to password history
        PasswordHistory::addPasswordHistory($user, $data['password']);

        // Log activity
        UserActivity::log('user_created', $user, [
            'created_by' => auth()->id(),
            'user_type' => $user->user_type,
        ]);

        return $user;
    }

    /**
     * Update user with admin panel features
     */
    public function updateUser(User $user, array $data): User
    {
        $originalData = $user->toArray();

        $user->update($data);

        // Log activity
        UserActivity::log('user_updated', $user, [
            'updated_by' => auth()->id(),
            'changes' => array_diff_assoc($data, $originalData),
        ]);

        return $user->fresh();
    }

    /**
     * Change user password with policy validation
     */
    public function changePassword(User $user, string $newPassword): array
    {
        $policy = $user->passwordPolicy ?? PasswordPolicy::getDefault();

        if (! $policy) {
            return ['success' => false, 'errors' => [__('app.errors.no_password_policy')]];
        }

        // Validate password against policy
        $errors = $policy->validatePassword($newPassword);
        if (! empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check password history
        if (PasswordHistory::isPasswordReused($user, $newPassword, $policy->password_history_count)) {
            return ['success' => false, 'errors' => [__('app.errors.password_reused')]];
        }

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
            'password_expires_at' => $policy->max_age_days
                ? now()->addDays($policy->max_age_days)
                : null,
            'force_password_change' => false,
        ]);

        // Add to password history
        PasswordHistory::addPasswordHistory($user, $newPassword);

        // Clean up old password history
        PasswordHistory::cleanupOldPasswords($user, $policy->password_history_count);

        // Log activity
        UserActivity::log('password_changed', $user);

        return ['success' => true];
    }

    /**
     * Handle login attempt
     */
    public function handleLoginAttempt(string $email, bool $successful, ?string $failureReason = null): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Log failed attempt for non-existent user
            LoginHistory::create([
                'user_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'successful' => false,
                'failure_reason' => 'user_not_found',
                'attempted_at' => now(),
            ]);

            return;
        }

        // Record login history
        LoginHistory::recordLogin($user, [
            'successful' => $successful,
            'failure_reason' => $failureReason,
        ]);

        if ($successful) {
            // Reset failed attempts and update last login
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => now(),
            ]);

            // Create session record
            UserSession::createSession($user, Session::getId());

            // Log activity
            UserActivity::log('user_login', $user);
        } else {
            // Increment failed attempts
            $policy = $user->passwordPolicy ?? PasswordPolicy::getDefault();
            $failedAttempts = $user->failed_login_attempts + 1;

            $updateData = ['failed_login_attempts' => $failedAttempts];

            // Lock account if threshold reached
            if ($policy && $failedAttempts >= $policy->lockout_attempts) {
                $updateData['locked_until'] = now()->addMinutes($policy->lockout_duration_minutes);

                UserActivity::log('user_locked', $user, [
                    'reason' => 'failed_login_attempts',
                    'attempts' => $failedAttempts,
                ]);
            }

            $user->update($updateData);
        }
    }

    /**
     * Lock user account
     */
    public function lockUser(User $user, ?int $durationMinutes = null): User
    {
        $policy = $user->passwordPolicy ?? PasswordPolicy::getDefault();
        $duration = $durationMinutes ?? $policy?->lockout_duration_minutes ?? 60;

        $user->update([
            'status' => 'locked',
            'locked_until' => now()->addMinutes($duration),
        ]);

        // Terminate all active sessions
        UserSession::terminateUserSessions($user);

        // Log activity
        UserActivity::log('user_locked', $user, [
            'locked_by' => auth()->id(),
            'duration_minutes' => $duration,
        ]);

        return $user;
    }

    /**
     * Unlock user account
     */
    public function unlockUser(User $user): User
    {
        $user->update([
            'status' => 'active',
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);

        // Log activity
        UserActivity::log('user_unlocked', $user, [
            'unlocked_by' => auth()->id(),
        ]);

        return $user;
    }

    /**
     * Suspend user account
     */
    public function suspendUser(User $user): User
    {
        $user->update(['status' => 'suspended']);

        // Terminate all active sessions
        UserSession::terminateUserSessions($user);

        // Log activity
        UserActivity::log('user_suspended', $user, [
            'suspended_by' => auth()->id(),
        ]);

        return $user;
    }

    /**
     * Activate user account
     */
    public function activateUser(User $user): User
    {
        $user->update(['status' => 'active']);

        // Log activity
        UserActivity::log('user_activated', $user, [
            'activated_by' => auth()->id(),
        ]);

        return $user;
    }

    /**
     * Terminate user sessions
     */
    public function terminateUserSessions(User $user, ?string $exceptSessionId = null): int
    {
        $terminated = UserSession::terminateUserSessions($user, $exceptSessionId);

        // Log activity
        UserActivity::log('sessions_terminated', $user, [
            'terminated_by' => auth()->id(),
            'sessions_count' => $terminated,
            'except_session' => $exceptSessionId,
        ]);

        return $terminated;
    }

    /**
     * Get user login statistics
     */
    public function getUserLoginStats(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $totalLogins = LoginHistory::forUser($user)
            ->where('attempted_at', '>=', $startDate)
            ->count();

        $successfulLogins = LoginHistory::forUser($user)
            ->successful()
            ->where('attempted_at', '>=', $startDate)
            ->count();

        $failedLogins = LoginHistory::forUser($user)
            ->failed()
            ->where('attempted_at', '>=', $startDate)
            ->count();

        return [
            'total_logins' => $totalLogins,
            'successful_logins' => $successfulLogins,
            'failed_logins' => $failedLogins,
            'success_rate' => $totalLogins > 0 ? round(($successfulLogins / $totalLogins) * 100, 2) : 0,
        ];
    }

    /**
     * Get user activity summary
     */
    public function getUserActivitySummary(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $activities = UserActivity::forUser($user)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        $totalActivities = array_sum($activities);

        return [
            'total_activities' => $totalActivities,
            'activities_by_type' => $activities,
            'most_common_action' => $totalActivities > 0 ? array_keys($activities, max($activities))[0] : null,
        ];
    }

    /**
     * Bulk operations on users
     */
    public function bulkUpdateUsers(Collection $users, array $data): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($users as $user) {
            try {
                $this->updateUser($user, $data);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "User {$user->email}: {$e->getMessage()}";
            }
        }

        // Log bulk operation
        UserActivity::log('bulk_user_update', null, [
            'updated_by' => auth()->id(),
            'user_count' => $users->count(),
            'success_count' => $results['success'],
            'failed_count' => $results['failed'],
            'changes' => $data,
        ]);

        return $results;
    }

    /**
     * Check if user account is locked
     */
    public function isUserLocked(User $user): bool
    {
        if ($user->status !== 'locked') {
            return false;
        }
        if (! $user->locked_until) {
            return false;
        }
        if ($user->locked_until->isFuture()) {
            return true;
        }
        // Auto-unlock if lock period has expired
        $user->update([
            'status' => 'active',
            'locked_until' => null,
        ]);

        return false;
    }

    /**
     * Check if password is expired
     */
    public function isPasswordExpired(User $user): bool
    {
        $policy = $user->passwordPolicy ?? PasswordPolicy::getDefault();

        return $policy ? $policy->isPasswordExpired($user) : false;
    }
}
