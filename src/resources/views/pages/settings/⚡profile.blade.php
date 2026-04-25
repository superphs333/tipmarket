<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('A new verification link has been sent to your email address.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    public function render()
    {
        return $this->view()->title(__('Profile settings'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('Profile')"
        :subheading="__('Update your profile image, name and email address')"
        subheadingClass="leading-8 tracking-[0.01em]"
        contentClass="mt-5 w-full"
    >
        <div class="w-full space-y-10" style="max-width: 48rem;">
            <section
                class="py-2"
                x-data="{
                    defaultProfileImageUrl: null,
                    profileImagePreviewUrl: null,
                    profileImageName: @js(__('No file chosen')),
                    tempProfileImageObjectUrl: null,
                    updateProfileImagePreview(event) {
                        const [file] = event.target.files ?? [];

                        if (this.tempProfileImageObjectUrl) {
                            URL.revokeObjectURL(this.tempProfileImageObjectUrl);
                            this.tempProfileImageObjectUrl = null;
                        }

                        if (! file) {
                            this.profileImageName = @js(__('No file chosen'));
                            this.profileImagePreviewUrl = this.defaultProfileImageUrl;

                            return;
                        }

                        this.profileImageName = file.name;
                        this.tempProfileImageObjectUrl = URL.createObjectURL(file);
                        this.profileImagePreviewUrl = this.tempProfileImageObjectUrl;
                    },
                    clearProfileImageSelection() {
                        if (this.$refs.profileImageInput) {
                            this.$refs.profileImageInput.value = '';
                        }

                        if (this.tempProfileImageObjectUrl) {
                            URL.revokeObjectURL(this.tempProfileImageObjectUrl);
                            this.tempProfileImageObjectUrl = null;
                        }

                        this.profileImageName = @js(__('No file chosen'));
                        this.profileImagePreviewUrl = this.defaultProfileImageUrl;
                    }
                }"
            >
                <div class="space-y-1">
                    <flux:heading class="text-xl">{{ __('Profile image') }}</flux:heading>
                    <flux:subheading>{{ __('Set a profile image to better represent your account.') }}</flux:subheading>
                </div>

                <div class="mt-10 flex flex-wrap items-start gap-8">
                    <div class="flex shrink-0 justify-start" style="width: 8.5rem;">
                        <div class="overflow-hidden rounded-full bg-zinc-100 shadow-sm dark:bg-zinc-800" style="width: 7.5rem; height: 7.5rem;">
                            <img
                                src=""
                                :src="profileImagePreviewUrl || ''"
                                alt="{{ __('Profile image') }}"
                                class="h-full w-full object-cover"
                                x-show="Boolean(profileImagePreviewUrl)"
                                style="display: none;"
                            >

                            <div
                                class="flex h-full w-full items-center justify-center bg-zinc-100 text-3xl font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                x-show="! profileImagePreviewUrl"
                            >
                                {{ auth()->user()->initials() }}
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 space-y-6" style="min-width: 18rem;">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <label
                                    for="profile_image"
                                    class="inline-flex h-12 cursor-pointer items-center justify-center rounded-xl border border-zinc-300 bg-white px-5 text-sm font-semibold text-zinc-900 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700"
                                >
                                    {{ __('Choose image') }}
                                </label>

                                <input
                                    id="profile_image"
                                    type="file"
                                    class="sr-only"
                                    accept="image/png,image/jpeg,image/webp"
                                    x-ref="profileImageInput"
                                    x-on:change="updateProfileImagePreview($event)"
                                >

                                <span
                                    class="min-w-0 text-sm font-medium text-zinc-500 dark:text-zinc-400"
                                    x-text="profileImageName"
                                >{{ __('No file chosen') }}</span>
                            </div>

                            <flux:text class="mt-3">{{ __('PNG, JPG, WEBP files are allowed and can be uploaded up to 2MB.') }}</flux:text>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <flux:button variant="primary" type="button" data-test="profile-image-save-button">
                                {{ __('Save') }}
                            </flux:button>

                            <flux:button
                                variant="danger"
                                type="button"
                                data-test="profile-image-delete-button"
                                x-on:click="clearProfileImageSelection()"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            </section>

            <form wire:submit="updateProfileInformation" class="w-full space-y-6">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

                <div>
                    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                    @if ($this->hasUnverifiedEmail)
                        <div>
                            <flux:text class="mt-4">
                                {{ __('Your email address is unverified.') }}

                                <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </flux:link>
                            </flux:text>

                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </form>

            @if ($this->showDeleteUser)
                <div class="w-full">
                    <livewire:pages::settings.delete-user-form />
                </div>
            @endif
        </div>
    </x-pages::settings.layout>
</section>
