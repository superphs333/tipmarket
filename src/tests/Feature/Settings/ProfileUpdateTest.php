<?php

use App\Enums\MediaCollection;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('Profile photo'));
});

test('profile photo save action is visible when no photo is saved or selected', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test('pages::settings.profile')
        ->assertSee(__('Save profile photo'))
        ->assertDontSee(__('Cancel selection'))
        ->assertDontSee(__('Delete profile photo'));
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        // Livewire select 입력값이 검증을 거쳐 users.locale에 저장되는지 확인한다.
        ->set('locale', 'en')
        ->call('updateProfileInformation');

    $response
        ->assertHasNoErrors()
        ->assertRedirectToRoute('profile.edit');

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->locale)->toEqual('en');
    expect($user->email_verified_at)->toBeNull();
});

test('profile image can be updated separately', function () {
    config(['media.disk' => 'public']);
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('avatar.png');

    $response = Livewire::test('pages::settings.profile')
        ->set('profileImage', $file)
        ->call('updateProfileImage');

    $response
        ->assertHasNoErrors()
        ->assertRedirectToRoute('profile.edit');

    $media = Media::query()
        ->where('owner_type', $user->getMorphClass())
        ->where('owner_id', $user->getKey())
        ->where('collection', MediaCollection::ProfileAvatar->value)
        ->first();

    expect($media)->not->toBeNull();
    expect($media->status)->toEqual(Media::STATUS_ATTACHED);

    Storage::disk('public')->assertExists($media->path);
});

test('profile image selection can be canceled before saving', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('profileImage', UploadedFile::fake()->image('avatar.png'))
        ->assertSee(__('Save profile photo'))
        ->assertSee(__('Cancel selection'))
        ->call('cancelProfileImageSelection')
        ->assertSet('profileImage', null)
        ->assertSee(__('Save profile photo'))
        ->assertDontSee(__('Cancel selection'));
});

test('profile image can be deleted when saved', function () {
    config(['media.disk' => 'public']);
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('profileImage', UploadedFile::fake()->image('avatar.png'))
        ->call('updateProfileImage');

    $avatar = $user->profileAvatar()->first();

    Livewire::test('pages::settings.profile')
        ->assertSee(__('Delete profile photo'))
        ->call('deleteProfileImage')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('profile.edit');

    expect($avatar->refresh()->trashed())->toBeTrue();
    expect($user->profileAvatar()->first())->toBeNull();

    Storage::disk('public')->assertMissing($avatar->path);
});

test('profile image update marks previous image as orphaned', function () {
    config(['media.disk' => 'public']);
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('profileImage', UploadedFile::fake()->image('first.png'))
        ->call('updateProfileImage');

    $firstAvatar = $user->profileAvatar()->first();

    Livewire::test('pages::settings.profile')
        ->set('profileImage', UploadedFile::fake()->image('second.png'))
        ->call('updateProfileImage')
        ->assertHasNoErrors();

    expect($firstAvatar->refresh()->status)->toEqual(Media::STATUS_ORPHANED);
    expect($user->profileAvatar()->first()->path)->not->toEqual($firstAvatar->path);
});

test('profile image must be an image', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('profileImage', UploadedFile::fake()->create('avatar.pdf', 128, 'application/pdf'))
        ->call('updateProfileImage')
        ->assertHasErrors(['profileImage']);
});

test('profile language must be supported', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        // config('app.supported_locales')에 없는 언어는 저장되지 않아야 한다.
        ->set('locale', 'fr')
        ->call('updateProfileInformation');

    $response->assertHasErrors(['locale']);
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
