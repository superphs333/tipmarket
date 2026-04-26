<?php

use App\Concerns\ProfileValidationRules;
use App\Services\Media\MediaPath;
use App\Services\Media\R2ImageStorageService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * [역할]
 * - 현재 로그인한 사용지의 이름/이메일 수정
 * - 프로필 이미지를 r2 저장소에 업로드
 * - 기존 프로필 이미지를 삭제
 * - 이메일 미인증 상태를 계산해서 화면에 보여줌
 * - 계정 삭제 폼을 보여줄지 여부를 계산 
 * 
 */   
new class extends Component {
    use ProfileValidationRules, WithFileUploads;
        // WithFileUploads => wire:model="profileImage"로 선택한 파일을 Livewire 임시 파일 객체로 다루게 해 줌

    public string $name = '';
    public string $email = '';
    public $profileImage = null;  // Livewire가 업로드 중인 임시 파일 객체를 보관.
    public ?string $profileImageUrl = null; // DB에 저장된 profile_image_path를 실제 표시 가능한 URL로 바꾼 값

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        // 저장된 path(R2 key)를 바로 img src에 쓰지 않고, 서비스에서 브라우저용 URL로 바꿔 초기 미리보기 상태를 맞춤 
        $this->profileImageUrl = $this->resolveProfileImageUrl($user->profile_image_path);
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

    // 실제 R2에 업로드 
    public function saveProfileImage(): void
    {

        $validated = $this->validate([
            'profileImage' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();
        $storage = app(R2ImageStorageService::class);

        // 정리하기 위한 이전 이미지 path
        $previousPath = $user->profile_image_path;

        // 업로드
        try {
            $newPath = $storage->store(
                $validated['profileImage'],
                MediaPath::userProfile($user->id),
                'profile',
            );
        } catch (\Throwable $exception) {
            report($exception);

            $this->addError('profileImage', __('Failed to upload profile image.'));

            return;
        }

        // 업로드 성공한 뒤에 DB path를 새 값으로꿈
        $user->forceFill([
            'profile_image_path' => $newPath,
        ])->save();

        if (filled($previousPath) && $previousPath !== $newPath) {
            try {
                $storage->delete($previousPath);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        // 업로드 완료 후 Livewire의 임시 파일 상태와 검증 에러를 비움 
        $this->reset('profileImage');
        $this->resetValidation('profileImage');

        // 새로 저장된 이미지를 즉시 미리보기에 반영
        $this->profileImageUrl = $this->resolveProfileImageUrl($newPath);

        Flux::toast(variant: 'success', text: __('Profile image updated.'));
    }

    // 아직 저장하지 않은 선택 상태만 취소할 때 사용 (실제 R2 파일 삭제는 하지 않음)
    public function cancelProfileImageSelection(): void
    {
        $this->reset('profileImage');
        $this->resetValidation('profileImage');
    }

    // DB에 저장된 이미지 삭제 
    public function deleteProfileImage(): void
    {
        $user = Auth::user();

        // DB에 저장된 이미지 path자체가 없으면 아무것도x
        if (blank($user->profile_image_path)) {
            return;
        }

        try {
            app(R2ImageStorageService::class)->delete($user->profile_image_path);
        } catch (\Throwable $exception) {
            report($exception);

            $this->addError('profileImage', __('Failed to delete profile image.'));

            return;
        }

        $user->forceFill([
            'profile_image_path' => null,
        ])->save();

        $this->reset('profileImage');
        $this->resetValidation('profileImage');
        $this->profileImageUrl = null;

        Flux::toast(variant: 'success', text: __('Profile image deleted.'));
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

    /**
     * DB에 저장된 프로필 이미지 경로(path)를, 화면에서 바로 쓸 수 있는 URL로 바꾸는 변환함수  
     */
    private function resolveProfileImageUrl(?string $path): ?string
    {
        // path가 없으면 이미지 대신 initials UI가 보이도록 null 반환
        if (blank($path)) {
            return null;
        }

        try {
            return app(R2ImageStorageService::class)->url($path);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
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
                wire:key="profile-image-section-{{ md5($profileImageUrl ?? 'none') }}"
                class="py-2"
                x-data="{
                    // 서버에서 내려준 '현재 저장된 이미지 URL'을 Alpine의 기준 상태로 둠
                    defaultProfileImageUrl: @js($profileImageUrl),

                    // 화면에 실제 보여줄 미리보기 URL (초기에는 기존 이미지, 파일 선택 후에는 object URL로 바뀜)
                    profileImagePreviewUrl: @js($profileImageUrl),
                    profileImageName: @js(__('No file chosen')),
                    tempProfileImageObjectUrl: null,

                    // 사용자가 파일 input에서 이미지를 고른 직후, 서버 저장 전에 브라우저에서만 미리보기를 갱신
                    updateProfileImagePreview(event) {
                        const [file] = event.target.files ?? [];

                        // 새 파일을 다시 고를 수 있으므로, 이전 object URL이 있으면 먼저 해제함. 
                        if (this.tempProfileImageObjectUrl) {
                            URL.revokeObjectURL(this.tempProfileImageObjectUrl);
                            this.tempProfileImageObjectUrl = null;
                        }

                        if (! file) {
                            // 선택 취소 시 -> 파일명/미리보기를 원래 저장된 이미지 상태로 복원
                            this.profileImageName = @js(__('No file chosen'));
                            this.profileImagePreviewUrl = this.defaultProfileImageUrl;

                            return;
                        }

                        // 저장 전에라도 즉시 미리보기가 보이게 브라우저 object URL을 사용함. 
                        this.profileImageName = file.name;
                        this.tempProfileImageObjectUrl = URL.createObjectURL(file);
                        this.profileImagePreviewUrl = this.tempProfileImageObjectUrl;
                    },

                    // 새로 선택한 파일 입력값과 미리보기 초기화 (선택 상태 초기화)
                    clearProfileImageSelection(syncWithServer = true) {

                        // 파일 input 자체의 값을 비움 (같은 파일을 다시 골라도 change 이벤트가 정상적으로 다시 발생함)
                        if (this.$refs.profileImageInput) {
                            this.$refs.profileImageInput.value = '';
                        }
                        // 브라우저에서 미리보기용으로 만든 object URL이 있으면 해제함 (브라우저 메모리에 임시 URL 남는 것 방지)
                        if (this.tempProfileImageObjectUrl) {
                            URL.revokeObjectURL(this.tempProfileImageObjectUrl);
                            this.tempProfileImageObjectUrl = null;
                        }

                        // 방금 고른 파일 상태를 없앴으므로 -> 파일명 표시도 초기값으로 되돌림
                        this.profileImageName = @js(__('No file chosen'));
                        this.profileImagePreviewUrl = this.defaultProfileImageUrl;

                        if (syncWithServer) {
                            // Lirvewire 쪽 임시 업로드 상태까지 함께 초기화해야, 프론트/서버 상태가 어긋나지 않음 
                            this.$wire.cancelProfileImageSelection();
                        }
                    },
                    // 상황에 따라 새로 고른 파일 선택을 취소하거나, 저장된 프로필 이미지 삭제를 서버에 요청 
                    deleteProfileImage() {
                        if (this.$refs.profileImageInput?.files?.length) {
                            // 아직 저장하지 않은 새 파일이 선택된 상태라면, 삭제는 원격 파일 삭제가 아니라 선택 취소로 해석 
                            this.clearProfileImageSelection();

                            return;
                        }

                        if (! this.defaultProfileImageUrl) {
                            return;
                        }

                        if (! window.confirm(@js(__('Are you sure you want to delete your profile image?')))) {
                            return;
                        }

                        // 저장된 실제 프로필 이미지 지우는 서버 호출 
                        this.$wire.deleteProfileImage();
                    },
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
                                    wire:model="profileImage"
                                    x-ref="profileImageInput"
                                    x-on:change="updateProfileImagePreview($event)"
                                >

                                <span
                                    class="min-w-0 text-sm font-medium text-zinc-500 dark:text-zinc-400"
                                    x-text="profileImageName"
                                >{{ __('No file chosen') }}</span>
                            </div>

                            <flux:text class="mt-3">{{ __('PNG, JPG, WEBP files are allowed and can be uploaded up to 2MB.') }}</flux:text>

                            @error('profileImage')
                                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <flux:button
                                variant="primary"
                                type="button"
                                wire:click="saveProfileImage"
                                wire:loading.attr="disabled"
                                wire:target="profileImage,saveProfileImage"
                                data-test="profile-image-save-button"
                            >
                                {{ __('Save') }}
                            </flux:button>

                            <flux:button
                                variant="danger"
                                type="button"
                                data-test="profile-image-delete-button"
                                wire:loading.attr="disabled"
                                wire:target="profileImage,saveProfileImage,deleteProfileImage"
                                x-on:click="deleteProfileImage()"
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
