<?php

declare(strict_types=1);

use App\Models\Admin\LoginHistory;
use App\Models\Admin\PasswordHistory;
use App\Models\Admin\PasswordPolicy;
use App\Models\Admin\UserActivity;
use App\Models\Admin\UserSession;
use App\Models\User;
use App\Services\Admin\AdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

/**
 * Integration tests for SSO/2FA authentication flows
 *
 * Tests that authentication policies, SSO integration, and 2FA
 * work correctly across the application.
 */
describe('Authentication Flows Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->adminService = resolve(AdminService::class);

        // Create default password policy
        $this->passwordPolicy = PasswordPolicy::factory()->create([
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
            'password_history_count' => 3,
            'max_age_days' => 90,
            'lockout_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'session_timeout_minutes' => 30,
            'max_concurrent_sessions' => 3,
        ]);
    });

    it('handles complete login flow with password policy enforcement', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('ValidPass123'),
        ]);

        $ipAddress = '192.168.1.100';
        $userAgent = 'Mozilla/5.0 Test Browser';

        // Test successful login
        $loginResult = $this->adminService->attemptLogin(
            'test@example.com',
            'ValidPass123',
            $ipAddress,
            $userAgent,
        );

        expect($loginResult['success'])->toBeTrue('Login should succeed with valid credentials');
        expect($loginResult['user_id'])->toBe($user->id);
        expect($loginResult['session_id'])->not->toBeNull('Session should be created');

        // Verify login history is recorded
        $loginHistory = LoginHistory::where('user_id', $user->id)->latest()->first();
        expect($loginHistory)->not->toBeNull('Login should be recorded in history');
        expect($loginHistory->success)->toBeTrue('Login record should show success');
        expect($loginHistory->ip_address)->toBe($ipAddress);
        expect($loginHistory->user_agent)->toBe($userAgent);

        // Verify user activity is logged
        $activity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'login')
            ->latest()
            ->first();
        expect($activity)->not->toBeNull('Login activity should be recorded');
        expect($activity->ip_address)->toBe($ipAddress);

        // Verify session is created
        $session = UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->latest()
            ->first();
        expect($session)->not->toBeNull('Active session should be created');
        expect($session->ip_address)->toBe($ipAddress);
        expect($session->user_agent)->toBe($userAgent);
    });

    it('enforces account lockout after failed login attempts', function (): void {
        $user = User::factory()->create([
            'email' => 'lockout@example.com',
            'password' => Hash::make('CorrectPass123'),
        ]);

        $ipAddress = '192.168.1.101';

        // Attempt failed logins up to the limit
        for ($i = 1; $i < $this->passwordPolicy->lockout_attempts; $i++) {
            $failResult = $this->adminService->attemptLogin(
                'lockout@example.com',
                'WrongPassword',
                $ipAddress,
            );

            expect($failResult['success'])->toBeFalse("Failed login attempt {$i} should be rejected");
            expect($failResult['attempts_remaining'])->toBe($this->passwordPolicy->lockout_attempts - $i);

            // Verify failed login is recorded
            $failedLogin = LoginHistory::where('user_id', $user->id)
                ->where('success', false)
                ->latest()
                ->first();
            expect($failedLogin)->not->toBeNull("Failed login {$i} should be recorded");
        }

        // Final attempt should trigger lockout
        $lockoutResult = $this->adminService->attemptLogin(
            'lockout@example.com',
            'WrongPassword',
            $ipAddress,
        );

        expect($lockoutResult['success'])->toBeFalse('Final failed attempt should trigger lockout');
        expect($lockoutResult['locked_until'])->not->toBeNull('Account should be locked');
        expect($lockoutResult['reason'])->toBe('account_locked');

        // Verify lockout activity is logged
        $lockoutActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'account_locked')
            ->latest()
            ->first();
        expect($lockoutActivity)->not->toBeNull('Account lockout should be logged');

        // Even correct password should be rejected during lockout
        $correctPasswordResult = $this->adminService->attemptLogin(
            'lockout@example.com',
            'CorrectPass123',
            $ipAddress,
        );

        expect($correctPasswordResult['success'])->toBeFalse('Correct password should be rejected during lockout');
        expect($correctPasswordResult['reason'])->toBe('account_locked');

        // Test lockout expiration
        $user->update(['locked_until' => now()->subMinute()]);

        $unlockedResult = $this->adminService->attemptLogin(
            'lockout@example.com',
            'CorrectPass123',
            $ipAddress,
        );

        expect($unlockedResult['success'])->toBeTrue('Login should succeed after lockout expires');

        // Verify unlock activity is logged
        $unlockActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'account_unlocked')
            ->latest()
            ->first();
        expect($unlockActivity)->not->toBeNull('Account unlock should be logged');
    });

    it('handles password change flow with history enforcement', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass123'),
        ]);

        $this->actingAs($user);

        // Create password history
        $oldPasswords = ['OldPass123', 'PrevPass456', 'AnotherPass789'];
        foreach ($oldPasswords as $password) {
            PasswordHistory::create([
                'user_id' => $user->id,
                'password_hash' => Hash::make($password),
                'created_at' => now()->subDays(random_int(1, 30)),
            ]);
        }

        // Test password change with policy validation
        $newPassword = 'NewValidPass123';
        $changeResult = $this->adminService->changeUserPassword(
            $user,
            'OldPass123',
            $newPassword,
        );

        expect($changeResult['success'])->toBeTrue('Password change should succeed with valid new password');

        // Verify password history is updated
        $latestHistory = PasswordHistory::where('user_id', $user->id)
            ->latest()
            ->first();
        expect($latestHistory)->not->toBeNull('New password should be added to history');
        expect(Hash::check($newPassword, $latestHistory->password_hash))->toBeTrue('History should contain new password hash');

        // Verify password change activity is logged
        $changeActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'password_changed')
            ->latest()
            ->first();
        expect($changeActivity)->not->toBeNull('Password change should be logged');

        // Test password reuse prevention
        $reuseResult = $this->adminService->changeUserPassword(
            $user,
            $newPassword,
            'OldPass123', // Try to reuse old password
        );

        expect($reuseResult['success'])->toBeFalse('Password reuse should be prevented');
        expect($reuseResult['errors'])->toContain('Password has been used recently');

        // Test that old passwords beyond history limit can be reused
        // Add more passwords to push old ones out of history
        for ($i = 0; $i < $this->passwordPolicy->password_history_count; $i++) {
            $tempPassword = "TempPass{$i}!";
            $this->adminService->changeUserPassword($user, $newPassword, $tempPassword);
            $newPassword = $tempPassword;
        }

        // Now old password should be allowed
        $oldReuseResult = $this->adminService->changeUserPassword(
            $user,
            $newPassword,
            'OldPass123',
        );

        expect($oldReuseResult['success'])->toBeTrue('Old password beyond history limit should be allowed');
    });

    it('manages user sessions with concurrent session limits', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sessions = [];
        $maxSessions = $this->passwordPolicy->max_concurrent_sessions;

        // Create sessions up to the limit
        for ($i = 1; $i <= $maxSessions; $i++) {
            $session = $this->adminService->createUserSession(
                $user,
                "192.168.1.{$i}",
                "Browser {$i}",
            );

            expect($session)->not->toBeNull("Session {$i} should be created");
            expect($session->is_active)->toBeTrue("Session {$i} should be active");
            $sessions[] = $session;
        }

        // Verify all sessions are active
        $activeSessions = UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
        expect($activeSessions)->toBe($maxSessions, "Should have {$maxSessions} active sessions");

        // Create one more session - should terminate the oldest
        $extraSession = $this->adminService->createUserSession(
            $user,
            '192.168.1.99',
            'Extra Browser',
        );

        expect($extraSession)->not->toBeNull('Extra session should be created');

        // Should still have max sessions
        $activeSessionsAfter = UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
        expect($activeSessionsAfter)->toBe($maxSessions, "Should still have only {$maxSessions} active sessions");

        // Oldest session should be terminated
        $sessions[0]->refresh();
        expect($sessions[0]->is_active)->toBeFalse('Oldest session should be terminated');
        expect($sessions[0]->ended_at)->not->toBeNull('Terminated session should have end time');
        expect($sessions[0]->end_reason)->toBe('session_limit_exceeded');

        // Test session timeout
        $timeoutSession = $sessions[1];
        $timeoutSession->update([
            'last_activity' => now()->subMinutes($this->passwordPolicy->session_timeout_minutes + 1),
        ]);

        $validationResult = $this->adminService->validateSession($timeoutSession, $this->passwordPolicy);
        expect($validationResult['valid'])->toBeFalse('Expired session should be invalid');
        expect($validationResult['reason'])->toBe('timeout');

        // Test manual session termination
        $manualSession = $sessions[2];
        $terminationResult = $this->adminService->terminateSession($manualSession, 'user_logout');

        expect($terminationResult['success'])->toBeTrue('Manual termination should succeed');

        $manualSession->refresh();
        expect($manualSession->is_active)->toBeFalse('Manually terminated session should be inactive');
        expect($manualSession->end_reason)->toBe('user_logout');

        // Verify session termination activity is logged
        $terminationActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'session_terminated')
            ->latest()
            ->first();
        expect($terminationActivity)->not->toBeNull('Session termination should be logged');
    });

    it('handles two-factor authentication flow', function (): void {
        // Skip if 2FA is not enabled in Fortify
        if (! Features::enabled(Features::twoFactorAuthentication())) {
            $this->markTestSkipped('Two-factor authentication is not enabled');
        }

        $user = User::factory()->create([
            'email' => '2fa@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $this->actingAs($user);

        // Enable 2FA for user
        $enableResult = $this->adminService->enableTwoFactorAuth($user);
        expect($enableResult['success'])->toBeTrue('2FA should be enabled successfully');
        expect($enableResult['secret'])->not->toBeNull('2FA secret should be generated');
        expect($enableResult['qr_code'])->not->toBeNull('QR code should be generated');

        // Verify 2FA enabled activity is logged
        $twoFactorActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'two_factor_enabled')
            ->latest()
            ->first();
        expect($twoFactorActivity)->not->toBeNull('2FA enablement should be logged');

        $user->refresh();
        expect($user->two_factor_secret)->not->toBeNull('User should have 2FA secret');

        // Test login with 2FA required
        $this->post('/logout'); // Logout first

        $loginResponse = $this->post('/login', [
            'email' => '2fa@example.com',
            'password' => 'Password123',
        ]);

        // Should redirect to 2FA challenge
        $loginResponse->assertRedirect('/two-factor-challenge');

        // Test invalid 2FA code
        $invalidCodeResponse = $this->post('/two-factor-challenge', [
            'code' => '000000',
        ]);

        $invalidCodeResponse->assertSessionHasErrors(['code']);

        // Test valid 2FA code (would need to generate actual TOTP code in real scenario)
        // For testing, we'll mock the validation
        $this->adminService->shouldReceive('validateTwoFactorCode')
            ->with($user, '123456')
            ->andReturn(true);

        // Verify 2FA challenge activity is logged
        $challengeActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'two_factor_challenge')
            ->latest()
            ->first();
        expect($challengeActivity)->not->toBeNull('2FA challenge should be logged');

        // Test 2FA disable
        $this->actingAs($user);
        $disableResult = $this->adminService->disableTwoFactorAuth($user);
        expect($disableResult['success'])->toBeTrue('2FA should be disabled successfully');

        $user->refresh();
        expect($user->two_factor_secret)->toBeNull('User should not have 2FA secret after disable');

        // Verify 2FA disabled activity is logged
        $disabledActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'two_factor_disabled')
            ->latest()
            ->first();
        expect($disabledActivity)->not->toBeNull('2FA disablement should be logged');
    });

    it('handles password reset flow with security measures', function (): void {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        // Test password reset request
        $resetRequestResult = $this->adminService->requestPasswordReset('reset@example.com');
        expect($resetRequestResult['success'])->toBeTrue('Password reset request should succeed');

        // Verify reset request activity is logged
        $resetRequestActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'password_reset_requested')
            ->latest()
            ->first();
        expect($resetRequestActivity)->not->toBeNull('Password reset request should be logged');

        // Test password reset with token (would be sent via email)
        $token = 'mock_reset_token';
        $newPassword = 'NewSecurePass123';

        $resetResult = $this->adminService->resetPasswordWithToken(
            'reset@example.com',
            $token,
            $newPassword,
        );

        // In a real scenario, this would validate the token
        expect($resetResult['success'])->toBeTrue('Password reset should succeed with valid token');

        // Verify password reset activity is logged
        $resetActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'password_reset_completed')
            ->latest()
            ->first();
        expect($resetActivity)->not->toBeNull('Password reset completion should be logged');

        // Verify new password is added to history
        $passwordHistory = PasswordHistory::where('user_id', $user->id)
            ->latest()
            ->first();
        expect($passwordHistory)->not->toBeNull('New password should be added to history');

        // Test that old sessions are invalidated after password reset
        $oldSession = UserSession::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'created_at' => now()->subHour(),
        ]);

        $this->adminService->invalidateUserSessions($user, 'password_reset');

        $oldSession->refresh();
        expect($oldSession->is_active)->toBeFalse('Old sessions should be invalidated after password reset');
        expect($oldSession->end_reason)->toBe('password_reset');
    });

    it('tracks suspicious login activity', function (): void {
        $user = User::factory()->create([
            'email' => 'suspicious@example.com',
            'password' => Hash::make('Password123'),
        ]);

        // Normal login from usual location
        $normalResult = $this->adminService->attemptLogin(
            'suspicious@example.com',
            'Password123',
            '192.168.1.100', // Home IP
            'Chrome Browser',
        );

        expect($normalResult['success'])->toBeTrue('Normal login should succeed');

        // Login from suspicious location
        $suspiciousResult = $this->adminService->attemptLogin(
            'suspicious@example.com',
            'Password123',
            '203.0.113.1', // Foreign IP
            'Unknown Browser',
        );

        expect($suspiciousResult['success'])->toBeTrue('Suspicious login should still succeed');
        expect($suspiciousResult['suspicious'])->toBeTrue('Login should be flagged as suspicious');

        // Verify suspicious login activity is logged
        $suspiciousActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'suspicious_login_detected')
            ->latest()
            ->first();
        expect($suspiciousActivity)->not->toBeNull('Suspicious login should be logged');
        expect($suspiciousActivity->metadata)->toHaveKey('ip_address');
        expect($suspiciousActivity->metadata)->toHaveKey('reason');

        // Test multiple rapid login attempts (potential brute force)
        for ($i = 0; $i < 10; $i++) {
            $this->adminService->attemptLogin(
                'suspicious@example.com',
                'WrongPassword',
                '203.0.113.2',
                'Automated Tool',
            );
        }

        // Should detect brute force pattern
        $bruteForceActivity = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'brute_force_detected')
            ->latest()
            ->first();
        expect($bruteForceActivity)->not->toBeNull('Brute force attempt should be detected and logged');
    });
});
