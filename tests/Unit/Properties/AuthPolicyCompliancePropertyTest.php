<?php

declare(strict_types=1);

use App\Models\Admin\LoginHistory;
use App\Models\Admin\PasswordPolicy;
use App\Models\Admin\UserActivity;
use App\Models\Admin\UserSession;
use App\Models\User;
use App\Services\Admin\AdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * **Feature: customization-administration, Property 4: Authentication policy compliance**
 *
 * Property: Password policies, SSO (LDAP/SAML/OAuth), and 2FA enforce secure authentication flows.
 *
 * This property tests that:
 * 1. Password policies are enforced consistently
 * 2. Login attempts are tracked and throttled
 * 3. Session management follows security policies
 * 4. Password history prevents reuse
 * 5. Account lockout policies are enforced
 */
it('enforces password policy compliance consistently', function (): void {
    $adminService = resolve(AdminService::class);

    // Create password policy
    $policy = PasswordPolicy::factory()->create([
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'password_history_count' => 3,
        'max_age_days' => 90,
        'lockout_attempts' => 3,
        'lockout_duration_minutes' => 15,
    ]);

    $user = User::factory()->create();

    // Property 1: Strong passwords should pass validation
    $strongPassword = 'StrongP@ss123';
    $validationResult = $adminService->validatePasswordPolicy($strongPassword, $policy);
    expect($validationResult['valid'])->toBeTrue('Strong password should pass policy validation');
    expect($validationResult['errors'])->toBeEmpty('Strong password should have no validation errors');

    // Property 2: Weak passwords should fail validation
    $weakPasswords = [
        'weak' => 'Password too short',
        'nouppercase' => 'Password missing uppercase',
        'NOLOWERCASE' => 'Password missing lowercase',
        'NoNumbers!' => 'Password missing numbers',
        'NoSymbols123' => 'Password missing symbols',
    ];

    foreach (array_keys($weakPasswords) as $weakPassword) {
        $weakResult = $adminService->validatePasswordPolicy($weakPassword, $policy);
        expect($weakResult['valid'])->toBeFalse("Weak password '{$weakPassword}' should fail validation");
        expect($weakResult['errors'])->not->toBeEmpty('Weak password should have validation errors');
    }

    // Property 3: Password history should prevent reuse
    $passwords = ['FirstP@ss123', 'SecondP@ss456', 'ThirdP@ss789'];

    foreach ($passwords as $password) {
        $adminService->updateUserPassword($user, $password);
    }

    // Attempting to reuse recent password should fail
    $reuseResult = $adminService->validatePasswordPolicy('FirstP@ss123', $policy, $user);
    expect($reuseResult['valid'])->toBeFalse('Recently used password should be rejected');
    expect($reuseResult['errors'])->toContain('Password has been used recently');

    // Property 4: Old passwords beyond history limit should be allowed
    $oldPassword = 'VeryOldP@ss000';
    $adminService->updateUserPassword($user, 'FourthP@ss012');

    // Now FirstP@ss123 should be allowed (beyond 3 password history)
    $oldReuseResult = $adminService->validatePasswordPolicy('FirstP@ss123', $policy, $user);
    expect($oldReuseResult['valid'])->toBeTrue('Password beyond history limit should be allowed');
});

/**
 * Property: Login throttling and lockout policies are enforced
 */
it('enforces login throttling and lockout policies', function (): void {
    $adminService = resolve(AdminService::class);

    $policy = PasswordPolicy::factory()->create([
        'lockout_attempts' => 3,
        'lockout_duration_minutes' => 15,
        'throttle_attempts' => 5,
        'throttle_duration_minutes' => 5,
    ]);

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('correct_password'),
    ]);

    $ipAddress = '192.168.1.100';

    // Property 1: Successful logins should be recorded
    $successResult = $adminService->attemptLogin('test@example.com', 'correct_password', $ipAddress);
    expect($successResult['success'])->toBeTrue('Correct credentials should allow login');

    $loginHistory = LoginHistory::where('user_id', $user->id)->latest()->first();
    expect($loginHistory)->not->toBeNull('Successful login should be recorded');
    expect($loginHistory->success)->toBeTrue('Login record should mark success');
    expect($loginHistory->ip_address)->toBe($ipAddress, 'Login record should capture IP address');

    // Property 2: Failed login attempts should be tracked
    for ($i = 1; $i <= 2; $i++) {
        $failResult = $adminService->attemptLogin('test@example.com', 'wrong_password', $ipAddress);
        expect($failResult['success'])->toBeFalse("Failed login attempt {$i} should be rejected");
        expect($failResult['attempts_remaining'])->toBe(3 - $i, 'Should show correct remaining attempts');
    }

    // Property 3: Account should be locked after max attempts
    $lockoutResult = $adminService->attemptLogin('test@example.com', 'wrong_password', $ipAddress);
    expect($lockoutResult['success'])->toBeFalse('Final failed attempt should trigger lockout');
    expect($lockoutResult['locked_until'])->not->toBeNull('Account should be locked with expiration time');

    // Property 4: Even correct password should be rejected during lockout
    $lockedResult = $adminService->attemptLogin('test@example.com', 'correct_password', $ipAddress);
    expect($lockedResult['success'])->toBeFalse('Correct password should be rejected during lockout');
    expect($lockedResult['reason'])->toBe('account_locked', 'Rejection reason should indicate lockout');

    // Property 5: Account should unlock after lockout duration
    // Simulate time passage by updating lockout expiration
    $user->update(['locked_until' => now()->subMinutes(1)]);

    $unlockedResult = $adminService->attemptLogin('test@example.com', 'correct_password', $ipAddress);
    expect($unlockedResult['success'])->toBeTrue('Account should unlock after lockout duration');

    // Property 6: Failed attempt counter should reset after successful login
    $user->refresh();
    expect($user->failed_login_attempts)->toBe(0, 'Failed attempt counter should reset after successful login');
});

/**
 * Property: Session management follows security policies
 */
it('enforces session security policies', function (): void {
    $adminService = resolve(AdminService::class);

    $policy = PasswordPolicy::factory()->create([
        'session_timeout_minutes' => 30,
        'max_concurrent_sessions' => 2,
        'require_session_validation' => true,
    ]);

    $user = User::factory()->create();
    $ipAddress1 = '192.168.1.100';
    $ipAddress2 = '192.168.1.101';
    $userAgent1 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
    $userAgent2 = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)';

    // Property 1: New sessions should be created and tracked
    $session1 = $adminService->createUserSession($user, $ipAddress1, $userAgent1);
    expect($session1)->not->toBeNull('Session should be created');
    expect($session1->user_id)->toBe($user->id, 'Session should be linked to user');
    expect($session1->ip_address)->toBe($ipAddress1, 'Session should record IP address');
    expect($session1->is_active)->toBeTrue('New session should be active');

    // Property 2: Multiple sessions should be allowed up to limit
    $session2 = $adminService->createUserSession($user, $ipAddress2, $userAgent2);
    expect($session2)->not->toBeNull('Second session should be created');

    $activeSessions = UserSession::where('user_id', $user->id)
        ->where('is_active', true)
        ->count();
    expect($activeSessions)->toBe(2, 'Should have 2 active sessions');

    // Property 3: Exceeding session limit should terminate oldest session
    $ipAddress3 = '192.168.1.102';
    $session3 = $adminService->createUserSession($user, $ipAddress3, 'Another User Agent');

    $activeSessionsAfter = UserSession::where('user_id', $user->id)
        ->where('is_active', true)
        ->count();
    expect($activeSessionsAfter)->toBe(2, 'Should still have only 2 active sessions');

    // Oldest session should be terminated
    $session1->refresh();
    expect($session1->is_active)->toBeFalse('Oldest session should be terminated');
    expect($session1->ended_at)->not->toBeNull('Terminated session should have end time');

    // Property 4: Session validation should detect expired sessions
    // Simulate expired session
    $session2->update(['last_activity' => now()->subMinutes(35)]);

    $validationResult = $adminService->validateSession($session2, $policy);
    expect($validationResult['valid'])->toBeFalse('Expired session should be invalid');
    expect($validationResult['reason'])->toBe('timeout', 'Invalid reason should be timeout');

    // Property 5: Active sessions should pass validation
    $session3->update(['last_activity' => now()->subMinutes(10)]);

    $activeValidation = $adminService->validateSession($session3, $policy);
    expect($activeValidation['valid'])->toBeTrue('Active session should be valid');

    // Property 6: Session termination should be recorded
    $adminService->terminateSession($session3, 'user_logout');

    $session3->refresh();
    expect($session3->is_active)->toBeFalse('Terminated session should be inactive');
    expect($session3->ended_at)->not->toBeNull('Terminated session should have end time');
    expect($session3->end_reason)->toBe('user_logout', 'Termination reason should be recorded');
});

/**
 * Property: User activity tracking is comprehensive
 */
it('tracks user activity comprehensively', function (): void {
    $adminService = resolve(AdminService::class);
    $user = User::factory()->create();
    $ipAddress = '192.168.1.100';

    // Property 1: Login activity should be tracked
    $adminService->logUserActivity($user, 'login', [
        'ip_address' => $ipAddress,
        'user_agent' => 'Test Browser',
    ]);

    $loginActivity = UserActivity::where('user_id', $user->id)
        ->where('activity_type', 'login')
        ->first();

    expect($loginActivity)->not->toBeNull('Login activity should be recorded');
    expect($loginActivity->ip_address)->toBe($ipAddress, 'Activity should record IP address');
    expect($loginActivity->metadata)->toHaveKey('user_agent', 'Activity should include metadata');

    // Property 2: Administrative actions should be tracked
    $adminService->logUserActivity($user, 'password_changed', [
        'changed_by' => 'admin',
        'policy_id' => 1,
    ]);

    $passwordActivity = UserActivity::where('user_id', $user->id)
        ->where('activity_type', 'password_changed')
        ->first();

    expect($passwordActivity)->not->toBeNull('Password change should be recorded');
    expect($passwordActivity->metadata)->toHaveKey('changed_by', 'Activity should include admin context');

    // Property 3: Security events should be tracked
    $securityEvents = [
        'account_locked',
        'account_unlocked',
        'password_reset_requested',
        'two_factor_enabled',
        'suspicious_login_detected',
    ];

    foreach ($securityEvents as $event) {
        $adminService->logUserActivity($user, $event, [
            'timestamp' => now()->toISOString(),
            'severity' => 'high',
        ]);
    }

    $securityActivities = UserActivity::where('user_id', $user->id)
        ->whereIn('activity_type', $securityEvents)
        ->count();

    expect($securityActivities)->toBe(count($securityEvents), 'All security events should be recorded');

    // Property 4: Activity queries should support filtering and pagination
    $recentActivities = $adminService->getUserActivities($user, [
        'since' => now()->subHour(),
        'limit' => 10,
        'types' => ['login', 'password_changed'],
    ]);

    expect($recentActivities)->toHaveCount(2, 'Filtered activities should match criteria');
    expect($recentActivities->pluck('activity_type')->toArray())
        ->toBe(['login', 'password_changed'], 'Activities should be filtered by type');

    // Property 5: Activity retention should be configurable
    $oldActivity = UserActivity::factory()->create([
        'user_id' => $user->id,
        'activity_type' => 'old_login',
        'created_at' => now()->subDays(400),
    ]);

    $cleanupResult = $adminService->cleanupOldActivities(365); // Keep 1 year
    expect($cleanupResult['deleted_count'])->toBeGreaterThan(0, 'Old activities should be cleaned up');

    $oldActivityExists = UserActivity::find($oldActivity->id);
    expect($oldActivityExists)->toBeNull('Old activity should be deleted');
});
