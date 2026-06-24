<?php

use App\Concerns\ProfileValidationRules;
use App\Enums\MediaCollection;
use App\Models\Media;
use App\Services\Media\MediaStorageService;
use Flux\Flux;
/* @chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-chisel-email-verification */
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component
{
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $locale = '';

    public ?TemporaryUploadedFile $profileImage = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        // 저장된 사용자 언어를 select 기본값으로 보여주고, 값이 없으면 앱 기본 언어를 사용한다.
        $this->locale = Auth::user()->locale ?? config('app.locale', 'ko');
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        // name, email, locale은 ProfileValidationRules를 통과한 값만 저장한다.
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));

        // 저장된 언어가 즉시 적용되도록 locale middleware가 다음 요청에서 다시 실행되게 한다.
        $this->redirectRoute('profile.edit');
    }

    /**
     * 현재 로그인한 사용자의 프로필 사진을 저장/교체
     * 
     * - 프로필 사진 저장 버튼을 눌렀을 때만 실행됨.
     */
    public function updateProfileImage(MediaStorageService $mediaStorage): void
    {
        // 사용자가 선택한 파일 검증 
        $validated = $this->validate($this->profileImageRules());
        // 현재 로그인한 사용자 
        $user = Auth::user();
        // 기존에 저장된 프로필 사진 있는지 조회 
        $previousAvatar = $user->profileAvatar()->first();
        // 새 프로필 사진 저장 
        $mediaStorage->store(
            file: $validated['profileImage'], // 검증을 통과한 업로드 파일 
            collection: MediaCollection::ProfileAvatar, // 파일의 용도 
            uploadedBy: $user, // 누가 업로드 했는지 기록 
            owner: $user, // 이 파일이 어떤 소속인지 기록 
        );

        // 기존 프로필 사진이 있었다면, orphaned 상태로 변경 
        if ($previousAvatar !== null) {
            $previousAvatar->update(['status' => Media::STATUS_ORPHANED]);
        }

        // Livewire 컴포넌트의 임시 파일 상태 초기화 
        $this->reset('profileImage');

        // 사용자에게 성공 메세지를 보여줌. 
        Flux::toast(variant: 'success', text: __('Profile photo updated.'));

        // 프로필 설정 페이지로 다시 이동. 
        $this->redirectRoute('profile.edit');
    }

    /**
     * 저장하기 전, 사용자가 방금 선택한 프로필 사진 파일을 취소. 
     * 
     * 주의]
     * - 이미 저장된 프로필 사진을 삭제하지 않음. 
     * - 현재 Livewire 컴포넌트에 임시로 선택된 파일만 비움. 
     */
    public function cancelProfileImageSelection(): void
    {
        $this->reset('profileImage');
        $this->resetValidation('profileImage'); // 검증 에러 초기화
    }

    /**
     * 현재 로그인한 사용자의 저장된 프로필 사진을 삭제 
     */
    public function deleteProfileImage(MediaStorageService $mediaStorage): void
    {
        $profileAvatar = Auth::user()->profileAvatar()->first();

        if ($profileAvatar === null) {
            return;
        }

        $mediaStorage->delete($profileAvatar);

        Flux::toast(variant: 'success', text: __('Profile photo deleted.'));

        $this->redirectRoute('profile.edit');
    }

    /* @chisel-email-verification */
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

        Session::flash('status', 'verification-link-sent');
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
    /* @end-chisel-email-verification */

    /**
     * Get supported display language options.
     *
     * @return array<string, string>
     */
    public function localeOptions(): array
    {
        // 화면 옵션과 검증 규칙이 같은 지원 언어 설정을 보도록 config를 사용한다.
        return config('app.supported_locales', []);
    }

    /**
     * 화면에 보여줄 프로필 사진 미리보기 URL  
     * 
     * [우선순위]
     * 1. 사용자가 새 파일을 선택 -> 새 파일의 임시 미리보기 URL
     * 2. 새 파일을 선택하지 않았다면 -> DB에 저장된 기존 프로필 사진 URL
     * 3. 둘 다 없다면 -> null 반환, flux:avatar가 이니셜을 보여줌.
     */
    #[Computed]
    public function profileImagePreviewUrl(): ?string
    {
        // 사용자가 파일 선택 버튼으로 새 프로필 사진 선택한 상태인지 확인
        if ($this->profileImage !== null) { 
            if (! $this->profileImage->isPreviewable()) { // 선택된 파일이 미리보기 가능한 파일인지 확인 
                return null;
            }
            // 선택된 새 파일의 임시 미리보기 url 반환 
            return $this->profileImage->temporaryUrl();
        }


        return Auth::user()->profileAvatar()->first()?->publicUrl();
    }

    /**
     * 사용자가 새 프로필 사진 파일을 선택했는지 여부를 계산 
     * 
     * [사용]
     * - 프로필 사진 저장 버튼 활성화 여부
     * - 선택 취소 버튼 표시 여부  
     */
    #[Computed]
    public function hasSelectedProfileImage(): bool
    {
        return $this->profileImage !== null;
    }

    /**
     * 현재 사용자에게 이미 저장된 프로필 사진이 있는지 계산 
     * 
     * - 프로필 사진 삭제 버튼 표시 여부 
     * 
     */
    #[Computed]
    public function hasSavedProfilePhoto(): bool
    {
        return Auth::user()->profileAvatar()->exists();
    }

    #[Computed]
    public function selectedProfileImageName(): string
    {
        return $this->profileImage?->getClientOriginalName() ?? __('No file selected');
    }

    /**
     * 프로필 사진 업로드에 사용할 검증 규칙 반환
     *
     * @return array<string, array<int, string>>
     */
    private function profileImageRules(): array
    {
        // profile_avatar  업로드 정책 가져옴. 
        $policy = config('media.collections.'.MediaCollection::ProfileAvatar->value, []);
        $maxKilobytes = (int) ceil(((int) ($policy['max_size'] ?? 2 * 1024 * 1024)) / 1024);
        $mimetypes = implode(',', (array) ($policy['mimes'] ?? ['image/jpeg', 'image/png', 'image/webp']));

        return [
            'profileImage' => [
                'required',
                'image',
                "mimetypes:{$mimetypes}",
                "max:{$maxKilobytes}",
            ],
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile settings')" :subheading="__('Manage the information shown on your account')">
        <form wire:submit="updateProfileImage" class="my-6 w-full space-y-4">
            <div>
                <flux:heading>{{ __('Profile photo') }}</flux:heading>
                <flux:text class="mt-1">{{ __('This photo appears in your account menu and activity.') }}</flux:text>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <flux:avatar
                    class="text-xl"
                    style="width: 6rem; height: 6rem;"
                    circle
                    :src="$this->profileImagePreviewUrl"
                    :name="Auth::user()->name"
                    :initials="Auth::user()->initials()"
                />

                <div class="space-y-3">
                    <flux:label for="profile-image">{{ __('Image file') }}</flux:label>
                    <flux:text>{{ __('JPG, PNG, or WebP up to 2MB.') }}</flux:text>

                    <div class="flex flex-wrap items-center gap-3">
                        <input
                            id="profile-image"
                            name="profile_image"
                            type="file"
                            wire:model="profileImage"
                            accept="image/jpeg,image/png,image/webp"
                            class="sr-only"
                        >

                        <label
                            for="profile-image"
                            class="inline-flex h-8 cursor-pointer items-center justify-center rounded-md border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-800 shadow-xs hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700"
                        >
                            {{ __('Choose file') }}
                        </label>

                        <flux:text>{{ $this->selectedProfileImageName }}</flux:text>
                    </div>

                    @error('profileImage')
                        <flux:text class="text-sm !text-red-600 dark:!text-red-400">{{ $message }}</flux:text>
                    @enderror

                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button
                            variant="primary"
                            type="submit"
                            :disabled="! $this->hasSelectedProfileImage"
                            data-test="update-profile-image-button"
                        >
                            {{ __('Save profile photo') }}
                        </flux:button>

                        @if ($this->hasSelectedProfileImage)
                            <flux:button type="button" wire:click="cancelProfileImageSelection" data-test="cancel-profile-image-button">
                                {{ __('Cancel selection') }}
                            </flux:button>
                        @elseif ($this->hasSavedProfilePhoto)
                            <flux:button variant="danger" type="button" wire:click="deleteProfileImage" data-test="delete-profile-image-button">
                                {{ __('Delete profile photo') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <flux:separator variant="subtle" class="my-8" />

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div>
                <flux:heading>{{ __('Basic information') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Update your name, email address, and language') }}</flux:text>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                {{-- @chisel-email-verification --}}
                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
                {{-- @end-chisel-email-verification --}}
            </div>

            <flux:select wire:model="locale" :label="__('Language')" required>
                @foreach ($this->localeOptions() as $locale => $label)
                    <flux:select.option :value="$locale">{{ __($label) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save basic information') }}
                    </flux:button>
                </div>

            </div>
        </form>

        <flux:separator variant="subtle" class="my-10" />

        {{-- @chisel-email-verification --}}
        @if ($this->showDeleteUser)
        {{-- @end-chisel-email-verification --}}
            <livewire:pages::settings.delete-user-form />
        {{-- @chisel-email-verification --}}
        @endif
        {{-- @end-chisel-email-verification --}}
    </x-pages::settings.layout>
</section>
