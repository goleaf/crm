<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Actions\Fortify\PasswordValidationRules;
use Filament\Actions\Action;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Hash;

final class Register extends BaseRegister
{
    use PasswordValidationRules;

    protected function getEmailFormComponent(): \Filament\Forms\Components\TextInput
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->email()
            ->rules(['email:rfc,dns'])
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/register.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rules(fn (Get $get): array => $this->passwordRules(
                input: ['email' => $get('email'), 'name' => $get('name')],
                requiresConfirmation: false,
            ))
            ->showAllValidationMessages()
            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::auth/pages/register.form.password.validation_attribute'));
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/register.form.actions.register.label'))
            ->submit('register');
    }
}
