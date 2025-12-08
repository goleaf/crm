<div class="space-y-4">
    <div class="sr-only">
        {{ __('profile.sections.update_profile_information.title') !== 'profile.sections.update_profile_information.title'
            ? __('profile.sections.update_profile_information.title')
            : __('Profile Information') }}
    </div>

    <form wire:submit="updateProfile">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals/>
</div>
