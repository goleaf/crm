<?php

declare(strict_types=1);

use App\Livewire\App\Profile\UpdatePassword as UpdatePasswordComponent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('password component renders correctly', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    Livewire::test(UpdatePasswordComponent::class)
        ->assertSuccessful()
        ->assertSee(__('profile.form.new_password.label'));
});

test('password can be updated', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test(UpdatePasswordComponent::class)
        ->fillForm([
            'currentPassword' => 'password',
            'password' => 'Sup3rSecure!2025',
            'password_confirmation' => 'Sup3rSecure!2025',
        ])
        ->call('updatePassword')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(Hash::check('Sup3rSecure!2025', $user->fresh()->password))->toBeTrue();
});

test('current password must be correct', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test(UpdatePasswordComponent::class)
        ->fillForm([
            'currentPassword' => 'wrong-password',
            'password' => 'Sup3rSecure!2025',
            'password_confirmation' => 'Sup3rSecure!2025',
        ])
        ->call('updatePassword')
        ->assertHasFormErrors(['currentPassword']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('new passwords must match', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test(UpdatePasswordComponent::class)
        ->fillForm([
            'currentPassword' => 'password',
            'password' => 'Sup3rSecure!2025',
            'password_confirmation' => 'AnotherSecure!2025',
        ])
        ->call('updatePassword')
        ->assertHasFormErrors(['password']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('password strength is enforced with zxcvbn', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    config(['zxcvbn.min_score' => 4]);

    Livewire::test(UpdatePasswordComponent::class)
        ->fillForm([
            'currentPassword' => 'password',
            'password' => 'N3wPass!2025',
            'password_confirmation' => 'N3wPass!2025',
        ])
        ->call('updatePassword')
        ->assertHasFormErrors(['password']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
